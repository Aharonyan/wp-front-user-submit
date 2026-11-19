/**
 * Page caches (cache plugins, host and CDN caches) can serve a form page that was built
 * hours or days ago. Its nonces may have expired by then, and the guest browser cookie was not
 * sent with it. Pages built with a page builder come without that cookie too: several files
 * uploaded at once would then get different browser ids. For guests on a page whose nonce
 * may have expired, or on a form with uploads without the cookie, ask the server for fresh
 * nonces and the cookie once, before the form uploads or sends anything.
 */

// A nonce is valid for at least half of its life; refresh this long before that point
const SAFETY_MARGIN_SECONDS = 10 * 60;

// Without an answer by then the form starts with the nonce of the page
const TIMEOUT_MS = 4000;

let request = null;

/**
 * @param {boolean} isGuest
 * @param {number} pageTime server time when the page was built (seconds)
 * @param {number} nonceLife nonce_life of the site (seconds)
 * @returns {boolean}
 */
export function isStalePage(isGuest, pageTime, nonceLife) {
    if (!isGuest || !pageTime) {
        return false;
    }
    const life = nonceLife > 0 ? nonceLife : 24 * 60 * 60;
    const refreshAfter = Math.max(life / 2 - SAFETY_MARGIN_SECONDS, life / 4);
    return Date.now() / 1000 - pageTime >= refreshAfter;
}

/**
 * @param {string} name cookie readable by JavaScript that the server sends with the browser id
 * @returns {boolean}
 */
export function hasCookie(name) {
    return Boolean(name) && document.cookie.split('; ').some((cookie) => cookie.startsWith(`${name}=`));
}

/**
 * One request per page, shared by all forms on it. A failed request is tried again next time.
 *
 * @param {string} url the fresh_nonces REST route
 * @returns {Promise<{rest: string, login: string}|null>} null when the request failed
 */
export default function freshNonces(url) {
    if (!request) {
        const controller = typeof AbortController === 'function' ? new AbortController() : null;
        const timer = controller ? setTimeout(() => controller.abort(), TIMEOUT_MS) : null;
        // no nonce header: the nonce of the page is the one that may have expired
        request = fetch(url, { method: 'POST', credentials: 'same-origin', signal: controller ? controller.signal : undefined })
            .then((response) => (response.ok ? response.json() : null))
            .catch(() => null)
            .then((nonces) => {
                clearTimeout(timer);
                if (!nonces) {
                    request = null;
                }
                return nonces;
            });
    }
    return request;
}

/**
 * Put a fresh REST nonce into the form data, also into upload URLs that carry it.
 * Changed in place: the form modules all hold this same window.editor_data object.
 *
 * @param {object} data window.editor_data
 * @param {string} nonce
 */
export function useRestNonce(data, nonce) {
    const old = data.nonce;
    if (!nonce || !old || nonce === old) {
        return;
    }
    Object.keys(data).forEach((key) => {
        if (typeof data[key] === 'string' && data[key].includes(old)) {
            data[key] = data[key].split(old).join(nonce);
        }
    });
}
