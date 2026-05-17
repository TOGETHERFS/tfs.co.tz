/**
 * JavaScript Translation Helper for Alpine.js Components
 * This provides translation support for dynamic content rendered via JavaScript
 */

window.AppTranslations = window.AppTranslations || {};

// Translation function that mirrors Laravel's __() helper
window.__ = function(key, replacements = {}) {
    // Get nested translation value
    const keys = key.split('.');
    let value = window.AppTranslations;
    
    for (const k of keys) {
        if (value && typeof value === 'object' && k in value) {
            value = value[k];
        } else {
            // Return the key if translation not found
            return key;
        }
    }
    
    // Handle replacements like :name, :count, etc.
    if (typeof value === 'string') {
        for (const [placeholder, replacement] of Object.entries(replacements)) {
            value = value.replace(new RegExp(`:${placeholder}`, 'g'), replacement);
        }
    }
    
    return value || key;
};

// Shorthand alias
window.trans = window.__;
