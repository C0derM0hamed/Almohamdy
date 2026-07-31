(function () {
    'use strict';

    var LIGHTBOX_ID = 'hmServicePhotoLightbox';
    var IMAGE_ID = 'hmServicePhotoLightboxImg';
    var OPEN_CLASS = 'hm-service-photo-lightbox-open';
    var TRIGGER_SELECTOR = '[data-hm-photo-lightbox]';
    var CLOSE_SELECTOR = '[data-hm-photo-lightbox-close]';

    function getLightbox() {
        return document.getElementById(LIGHTBOX_ID);
    }

    function getImage() {
        return document.getElementById(IMAGE_ID);
    }

    function isOpen() {
        var lightbox = getLightbox();

        return lightbox !== null && !lightbox.hidden;
    }

    function resolveClickTarget(event) {
        var target = event.target;

        if (target instanceof Element) {
            return target;
        }

        if (target && target.parentElement instanceof Element) {
            return target.parentElement;
        }

        return null;
    }

    function openLightbox(source, altText) {
        var lightbox = getLightbox();
        var image = getImage();

        if (!lightbox || !image || !source) {
            return;
        }

        if (lightbox.parentElement !== document.body) {
            document.body.appendChild(lightbox);
        }

        image.src = source;
        image.alt = altText || '';
        lightbox.hidden = false;
        lightbox.setAttribute('aria-hidden', 'false');
        document.body.classList.add(OPEN_CLASS);
    }

    function closeLightbox() {
        var lightbox = getLightbox();
        var image = getImage();

        if (!lightbox) {
            return;
        }

        lightbox.hidden = true;
        lightbox.setAttribute('aria-hidden', 'true');
        document.body.classList.remove(OPEN_CLASS);

        if (image) {
            image.removeAttribute('src');
            image.alt = '';
        }
    }

    function initServicePhotoLightbox() {
        document.addEventListener('click', function (event) {
            var clickTarget = resolveClickTarget(event);

            if (!clickTarget) {
                return;
            }

            var trigger = clickTarget.closest(TRIGGER_SELECTOR);

            if (trigger) {
                event.preventDefault();
                event.stopPropagation();

                var img = trigger.querySelector('img');
                var source = trigger.getAttribute('data-photo-src')
                    || (img ? img.getAttribute('src') : '')
                    || '';

                var altText = trigger.getAttribute('data-photo-alt')
                    || (img ? img.getAttribute('alt') : '')
                    || '';

                openLightbox(source, altText);
                return;
            }

            if (isOpen() && clickTarget.closest(CLOSE_SELECTOR)) {
                event.preventDefault();
                closeLightbox();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && isOpen()) {
                event.preventDefault();
                closeLightbox();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initServicePhotoLightbox);
    } else {
        initServicePhotoLightbox();
    }
})();
