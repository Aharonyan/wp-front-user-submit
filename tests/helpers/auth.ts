import { type Page, expect } from '@playwright/test';

/** WordPress wp-login.php authentication */
export async function wpLogin(page: Page, username: string, password: string) {
  await page.goto('/wp-login.php');
  await page.fill('#user_login', username);
  await page.fill('#user_pass', password);
  await page.click('#wp-submit');
  await page.waitForURL(url => !url.pathname.includes('wp-login.php'));
}

/** Login via the front-end FUS login form */
export async function frontendLogin(page: Page, loginPageUrl: string, username: string, password: string) {
  await page.goto(loginPageUrl);
  await page.fill('input[name="fus_username"]', username);
  await page.fill('input[name="fus_password"]', password);
  await page.click('input.fus-submit-btn[type="submit"]');
  // Wait for AJAX redirect or success message
  await page.waitForFunction(() => {
    const msg = document.querySelector('.fus-message.success');
    return msg && msg.textContent && msg.textContent.trim().length > 0;
  }, { timeout: 15_000 }).catch(() => {
    // Fallback: the page might have already redirected
  });
  // Give time for redirect
  await page.waitForTimeout(2000);
}

/** Logout the current user via wp-login.php?action=logout */
export async function wpLogout(page: Page) {
  await page.goto('/wp-login.php?action=logout');
  // WordPress shows a confirmation link, click it
  const confirmLink = page.locator('a[href*="action=logout"]');
  if (await confirmLink.count() > 0) {
    await confirmLink.first().click();
  }
  await page.waitForTimeout(1000);
}
