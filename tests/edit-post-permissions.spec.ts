import { test, expect, type Page } from '@playwright/test';
import { wpLogin, wpLogout } from './helpers/auth';
import { TEST_CONFIG } from './helpers/config';

const { admin, user, urls, formId } = TEST_CONFIG;

/**
 * -----------------------------------------------------------------
 *  Edit Post Permissions  –  Front User Submit
 * -----------------------------------------------------------------
 *  Tests the fix in Editor.php → can_edit_post():
 *    Post authors can edit their own front-end submissions
 *    regardless of WordPress role, while the
 *    "Lock User From Editing After" feature still works.
 * -----------------------------------------------------------------
 */

// ─── Admin helpers ────────────────────────────────────────────────

/** Navigate to the form's "Edit Post Settings" sub-tab and return the page */
async function goToEditPostSettings(page: Page) {
  await page.goto(`${urls.formSettings}#post-form-settings`);
  // Click the top "Settings" tab
  await page.click('a.nav-tab.top[href="#post-form-settings"]');
  await page.waitForTimeout(500);
  // Click the "Edit Post Settings" sub-tab
  await page.click('a.nav-tab.sub[href="#fe-metabox-settings-update"]');
  await page.waitForTimeout(500);
}

/** Set the "Lock User From Editing After" value and save the form */
async function setLockHours(page: Page, hours: string) {
  await goToEditPostSettings(page);

  const lockInput = page.locator('input[name="settings[post_update_lock_user_after]"]');
  await lockInput.fill(hours);

  // Save form
  await page.click('#save-form-post');
  await page.waitForTimeout(2000);
}

/** Clear the lock setting (set to empty) and save */
async function clearLockHours(page: Page) {
  await goToEditPostSettings(page);

  const lockInput = page.locator('input[name="settings[post_update_lock_user_after]"]');
  await lockInput.fill('');

  await page.click('#save-form-post');
  await page.waitForTimeout(2000);
}

// ─── User helpers ─────────────────────────────────────────────────

/** Submit a new post via the front-end form. Returns the post_id. */
async function submitNewPost(page: Page, title: string): Promise<string> {
  await page.goto(urls.formPage);

  // Wait for the form to render
  await page.waitForSelector('form.fus-form', { timeout: 15_000 });

  // Fill the post title
  const titleField = page.locator('#fus_post_title');
  await titleField.waitFor({ state: 'visible', timeout: 10_000 });
  await titleField.fill(title);

  // Click submit
  await page.click('button.form-submit');

  // Wait for success: either message-wrap success or SweetAlert
  await Promise.race([
    page.waitForSelector('#fus-message-wrap.success', { timeout: 20_000 }).catch(() => null),
    page.waitForSelector('.swal2-popup', { timeout: 20_000 }).catch(() => null),
  ]);

  // Wait a bit for DOM to stabilize
  await page.waitForTimeout(2000);

  // Get the post_id from the hidden input (updated after save)
  const postId = await page.locator('input[name="post_id"]').inputValue();
  return postId;
}

/** Try to navigate to the edit page for a given post_id */
async function goToEditPost(page: Page, postId: string) {
  await page.goto(`${urls.formPage}?post_id=${postId}`);
  await page.waitForTimeout(3000);
}

// ─── Tests ────────────────────────────────────────────────────────

test.describe('Edit Post Permissions', () => {

  test.describe.serial('Setup & Author can edit own post', () => {
    let createdPostId: string;

    test('Admin: clear lock setting so editing is unrestricted', async ({ page }) => {
      await wpLogin(page, admin.username, admin.password);
      await clearLockHours(page);
    });

    test('User: can submit a new post via front-end form', async ({ page }) => {
      await wpLogin(page, user.username, user.password);
      await page.goto(urls.formPage);

      // The form should be visible (no error message)
      const form = page.locator('form.fus-form');
      await expect(form).toBeVisible({ timeout: 15_000 });

      // There should be no restriction message
      const restrictionMsg = page.locator('.fus-info');
      await expect(restrictionMsg).toHaveCount(0);

      // Submit a post
      const testTitle = `Playwright Test Post ${Date.now()}`;
      createdPostId = await submitNewPost(page, testTitle);

      expect(createdPostId).toBeTruthy();
      expect(createdPostId).not.toBe('new');
    });

    test('User: can edit own submitted post', async ({ page }) => {
      test.skip(!createdPostId, 'No post was created in previous test');

      await wpLogin(page, user.username, user.password);
      await goToEditPost(page, createdPostId);

      // The form should load (not an error)
      const form = page.locator('form.fus-form');
      await expect(form).toBeVisible({ timeout: 15_000 });

      // There should be no restriction message blocking the form
      const restrictionMsg = page.locator('.fus-info');
      await expect(restrictionMsg).toHaveCount(0);

      // The title field should have our post's title
      const titleField = page.locator('#fus_post_title');
      await expect(titleField).toBeVisible();
      const titleValue = await titleField.inputValue();
      expect(titleValue.length).toBeGreaterThan(0);
    });
  });

  test.describe.serial('Lock User From Editing After', () => {
    let lockedPostId: string;

    test('Admin: set lock to a very small value (0.001 hours ≈ 3.6s)', async ({ page }) => {
      await wpLogin(page, admin.username, admin.password);
      await goToEditPostSettings(page);

      // Remove the min attribute so we can set a tiny value
      const lockInput = page.locator('input[name="settings[post_update_lock_user_after]"]');
      await lockInput.evaluate((el: HTMLInputElement) => el.removeAttribute('min'));
      await lockInput.fill('0.001');

      await page.click('#save-form-post');
      await page.waitForTimeout(2000);
    });

    test('User: submit a post (lock is active)', async ({ page }) => {
      await wpLogin(page, user.username, user.password);
      const testTitle = `Lock Test Post ${Date.now()}`;
      lockedPostId = await submitNewPost(page, testTitle);

      expect(lockedPostId).toBeTruthy();
      expect(lockedPostId).not.toBe('new');
    });

    test('User: blocked from editing after lock time passes', async ({ page }) => {
      test.skip(!lockedPostId, 'No post was created in previous test');

      // Wait for the lock to expire (0.001 hours ≈ 3.6 seconds, wait 6s to be safe)
      await page.waitForTimeout(6000);

      await wpLogin(page, user.username, user.password);
      await goToEditPost(page, lockedPostId);

      // Should see the restriction message
      const restrictionMsg = page.locator('.fus-info');
      await expect(restrictionMsg).toBeVisible({ timeout: 10_000 });

      // The form submit button should NOT be visible
      const form = page.locator('form.fus-form button.form-submit');
      await expect(form).toHaveCount(0);
    });

    test('Admin: cleanup - clear lock setting', async ({ page }) => {
      await wpLogin(page, admin.username, admin.password);
      await clearLockHours(page);
    });
  });

  test.describe.serial('Non-author cannot edit other users posts', () => {
    let adminPostId: string;

    test('Admin: submit a post via the form', async ({ page }) => {
      await wpLogin(page, admin.username, admin.password);

      // Make sure lock is cleared
      await clearLockHours(page);

      const testTitle = `Admin Only Post ${Date.now()}`;
      adminPostId = await submitNewPost(page, testTitle);

      expect(adminPostId).toBeTruthy();
      expect(adminPostId).not.toBe('new');
    });

    test('User: cannot edit post authored by admin', async ({ page }) => {
      test.skip(!adminPostId, 'No admin post was created');

      await wpLogin(page, user.username, user.password);
      await goToEditPost(page, adminPostId);

      // Should see restriction message (not the form)
      const restrictionMsg = page.locator('.fus-info');
      await expect(restrictionMsg).toBeVisible({ timeout: 10_000 });

      const msgText = await restrictionMsg.textContent();
      expect(msgText).toContain('not allowed to edit');
    });
  });

  test.describe('Frontend login form works', () => {
    test('User can log in via the frontend login form', async ({ page }) => {
      // Make sure we're logged out
      await page.goto('/wp-login.php?action=logout');
      const confirmLink = page.locator('a[href*="action=logout"]');
      if (await confirmLink.count() > 0) {
        await confirmLink.first().click();
        await page.waitForTimeout(1000);
      }

      // Go to frontend login
      await page.goto(urls.loginForm);

      const loginForm = page.locator('.fus-login-form-wrap');
      await expect(loginForm).toBeVisible({ timeout: 10_000 });

      await page.fill('input[name="fus_username"]', user.username);
      await page.fill('input[name="fus_password"]', user.password);
      await page.click('input.fus-submit-btn[type="submit"]');

      // Wait for success message or redirect
      await Promise.race([
        page.waitForSelector('.fus-message.success', { timeout: 15_000 }).catch(() => null),
        page.waitForURL(url => !url.pathname.includes('login'), { timeout: 15_000 }).catch(() => null),
      ]);

      // After login, verify we can access the form page
      await page.goto(urls.formPage);
      const form = page.locator('form.fus-form');
      await expect(form).toBeVisible({ timeout: 15_000 });
    });
  });
});
