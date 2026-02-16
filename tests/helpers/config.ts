/**
 * Test environment configuration.
 * Credentials and URLs for the local WordPress test site.
 */
export const TEST_CONFIG = {
  /** Admin credentials */
  admin: {
    username: 'root',
    password: 'root',
  },

  /** Simple user (subscriber/contributor/author) credentials */
  user: {
    username: 'claude',
    password: 'claude',
  },

  /** URLs */
  urls: {
    wpAdmin: '/wp-admin',
    wpLogin: '/wp-login.php',
    formSettings: '/wp-admin/admin.php?page=fe-post-forms&action=edit&id=460',
    formPage: '/claude-test-form/',
    loginForm: '/login-form/',
  },

  /** Form ID used in tests */
  formId: 460,
};
