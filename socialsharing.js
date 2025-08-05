const processedElements = new WeakSet();

    function sanitizeUrl(urlString) {
        try {
            const url = new URL(urlString, window.location.href);
            return url.href;
        } catch {
            return window.location.href;
        }
    }

    const SocialSharing = {
        initSocialSharing: function(selector) {
            const elements = document.querySelectorAll(selector || '.kpj-social-share');
            elements.forEach(el => {
                if (processedElements.has(el)) {
                    return;
                }
                processedElements.add(el);
                const network = (el.getAttribute('data-network') || '').toLowerCase();
                if (!el.getAttribute('aria-label')) {
                    const label = 'Share on ' + network.charAt(0).toUpperCase() + network.slice(1);
                    el.setAttribute('aria-label', label);
                }
                el.addEventListener('click', function(e) {
                    e.preventDefault();
                    const data = {
                        url: sanitizeUrl(el.getAttribute('data-url') || window.location.href),
                        text: el.getAttribute('data-text') || document.title,
                        title: el.getAttribute('data-title') || document.title,
                        via: el.getAttribute('data-via') || '',
                        hashtags: el.getAttribute('data-hashtags') || ''
                    };
                    switch (network) {
                        case 'facebook':
                            SocialSharing.shareToFacebook(data);
                            break;
                        case 'twitter':
                            SocialSharing.shareToTwitter(data);
                            break;
                        case 'linkedin':
                            SocialSharing.shareToLinkedIn(data);
                            break;
                        default:
                    }
                });
            });
        },

        shareToFacebook: function(data) {
            const shareUrl = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(data.url);
            SocialSharing.openPopup(shareUrl, 'FacebookShare');
        },

        shareToTwitter: function(data) {
            const params = new URLSearchParams();
            params.set('url', data.url);
            params.set('text', data.text);
            if (data.via) {
                params.set('via', data.via);
            }
            if (data.hashtags) {
                params.set('hashtags', data.hashtags);
            }
            const shareUrl = 'https://twitter.com/intent/tweet?' + params.toString();
            SocialSharing.openPopup(shareUrl, 'TwitterShare');
        },

        shareToLinkedIn: function(data) {
            const shareUrl = 'https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(data.url);
            SocialSharing.openPopup(shareUrl, 'LinkedInShare');
        },

        openPopup: function(url, name) {
            const width = 600;
            const height = 400;
            const left = (window.screen.width / 2) - (width / 2);
            const top = (window.screen.height / 2) - (height / 2);
            window.open(
                url,
                name,
                'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,width=' + width + ',height=' + height + ',top=' + top + ',left=' + left
            );
        }
    };

    if (!window.KPJ) {
        window.KPJ = {};
    }
    window.KPJ.SocialSharing = SocialSharing;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            SocialSharing.initSocialSharing();
        });
    } else {
        SocialSharing.initSocialSharing();
    }

})(window, document);