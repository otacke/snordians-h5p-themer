(() => {
  /** @constant {string[]} VALID_DENSITY_CLASSES Valid density classed for H5P Theming. */
  const VALID_DENSITY_CLASSES = ['h5p-large', 'h5p-medium', 'h5p-small'];

  /** @constant {string} COLOR_KEY_PREFIX Prefix for H5P Theming CSS properties. */
  const COLOR_KEY_PREFIX = '--h5p-theme-';

  /** @constant {Set<string>} VALID_COLOR_KEYS Set of valid CSS property keys for H5P Theming. */
  const VALID_COLOR_KEYS = new Set([
    'ui-base', 'text-primary', 'text-secondary', 'text-third', 'stroke-1', 'stroke-2', 'stroke-3',
    'feedback-correct-main', 'feedback-correct-secondary', 'feedback-correct-third', 'feedback-incorrect-main',
    'feedback-incorrect-secondary', 'feedback-incorrect-third', 'feedback-neutral-main', 'feedback-neutral-secondary',
    'feedback-neutral-third', 'main-cta-base', 'secondary-cta-base', 'alternative-base', 'background', 'focus',
    'main-cta-light', 'main-cta-dark', 'contrast-cta', 'contrast-cta-white', 'contrast-cta-light', 'contrast-cta-dark',
    'secondary-cta-light', 'secondary-cta-dark', 'secondary-contrast-cta', 'secondary-contrast-cta-hover',
    'alternative-light', 'alternative-dark', 'alternative-darker'
  ].map((key) => `${COLOR_KEY_PREFIX}${key}`));

  /** @constant {number} FAILSAFE_INITIALIZATION_TIMEOUT_MS Time in milliseconds to wait before assuming error. */
  const FAILSAFE_INITIALIZATION_TIMEOUT_MS = 1500;

  let handledInitialization = false;
  let initializationFailsafeTimeout;
  let densityApplied = false;

  /**
   * Apply theme colors from themer configuration to document root.
   * @param {object} themer Themer configuration object.
   */
  const applyThemeColors = (themer) => {
    if (!themer?.colors) {
      return;
    }

    const colors = themer.colors;
    if (typeof colors !== 'object' || Array.isArray(colors) || colors === null) {
      console.warn('Invalid colors format in H5P_THEMER configuration. Skipping color theming.');
      return;
    }

    const rootStyle = document.documentElement.style;
    Object.entries(colors).forEach(([key, value]) => {
      if (!VALID_COLOR_KEYS.has(key) || typeof value !== 'string') {
        console.warn(`Invalid color key or value in H5P_THEMER configuration: ${key}. Skipping this color.`);
        return;
      }

      rootStyle.setProperty(key, value);
    });
  };

  /**
   * Apply density setting.
   * @param {HTMLElement} h5pContent H5P content element to apply density class to.
   * @param {object} themer Themer configuration object.
   */
  const applyDensity = (h5pContent, themer) => {
    const density = themer.density;
    if (!density || density === '') {
      return;
    }

    if (!VALID_DENSITY_CLASSES.includes(density)) {
      console.warn(`Invalid density value in H5P_THEMER configuration: ${density}. Skipping density theming.`);
      return;
    }

    h5pContent.classList.remove(...VALID_DENSITY_CLASSES);
    h5pContent.classList.add(density);

    window.H5P?.instances?.[0]?.trigger('resize');
  };

  /**
   * Hide H5P content.
   * @param {HTMLElement} h5pContent H5P content element to hide.
   */
  const hideH5PContent = (h5pContent) => {
    h5pContent.style.setProperty('display', 'none');
  };

  /**
   * Show H5P content.
   * @param {HTMLElement} h5pContent H5P content element to show.
   */
  const showContent = (h5pContent) => {
    h5pContent.style.removeProperty('display');
  };

  /**
   * Handle H5P content initialization.
   * @param {HTMLElement} h5pContent H5P content element being initialized.
   * @param {object} themer Themer configuration object.
   */
  const handleContentInitialized = (h5pContent, themer) => {
    if (handledInitialization) {
      return;
    }

    handledInitialization = true;
    window.clearTimeout(initializationFailsafeTimeout);

    applyDensity(h5pContent, themer);

    window.requestAnimationFrame(() => {
      showContent(h5pContent);
    });
  };

  /**
   * Main function to run on DOM ready.
   */
  const run = () => {
    const themer = window.H5P_THEMER;
    if (!themer) {
      console.warn('H5P_THEMER configuration object not found. Skipping theming.');
      return;
    }

    handledInitialization = false;

    if (window.H5PIntegration) {
      window.H5PIntegration.theme = window.H5PIntegration.theme ?? {};
      window.H5PIntegration.theme.density = themer.density ?? window.H5PIntegration.theme.density;
      densityApplied = true;
    }

    const h5pContent = document.querySelector('.h5p-content');
    if (!h5pContent) {
      console.warn('H5P content element not found. Skipping theming.');
      return;
    }

    window.clearTimeout(initializationFailsafeTimeout);

    applyThemeColors(themer);

    if (densityApplied) {
      return; // Could already be applied via H5PIntegration
    }

    hideH5PContent(h5pContent);

    const h5pExternaldispatcher = window.H5P?.externalDispatcher;
    if (!h5pExternaldispatcher) {
      console.warn('H5P externalDispatcher not found. Skipping density theming.');
      showContent(h5pContent);
      return;
    }

    if (window.H5P?.instances?.[0]) {
      handleContentInitialized(h5pContent, themer);
      return;
    }

    h5pExternaldispatcher.once('initialized', () => {
      handleContentInitialized(h5pContent, themer);
    });

    initializationFailsafeTimeout = window.setTimeout(() => {
      if (handledInitialization) {
        return;
      }

      handledInitialization = true;
      showContent(h5pContent);
      console.warn('H5P instance initialization timeout reached. Showing content without applied density.');
    }, FAILSAFE_INITIALIZATION_TIMEOUT_MS);
  };

  /**
   * Handle DOM ready state and execute callback.
   * @param {function} callback Function to execute when DOM is ready.
   */
  const onDOMReady = (callback = () => {}) => {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', callback, { once: true });
      return;
    }

    callback();
  };

  // Execute the main function on DOM ready.
  onDOMReady(run);
})();
