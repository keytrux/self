import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    initDebouncedSearch();
    initCarousels();

    const scanButton = document.getElementById('scan-barcode-button');
    if (scanButton) {
        initBarcodeScanner(scanButton);
    }
});

function initDebouncedSearch() {
    const inputs = document.querySelectorAll('[data-debounce-submit]');
    inputs.forEach((input) => {
        let timer = null;
        input.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => {
                const form = document.querySelector(input.dataset.debounceSubmit);
                if (form) {
                    form.submit();
                }
            }, 300);
        });
    });
}

function initCarousels() {
    document.querySelectorAll('[data-carousel]').forEach((carousel) => {
        const track = carousel.querySelector('[data-carousel-track]');
        const slides = carousel.querySelectorAll('[data-carousel-slide]');
        const dots = carousel.querySelectorAll('[data-carousel-dot]');
        const next = carousel.querySelector('[data-carousel-next]');
        const prev = carousel.querySelector('[data-carousel-prev]');
        let current = 0;

        if (slides.length <= 1) {
            return;
        }

        const update = () => {
            track.style.transform = `translateX(-${current * 100}%)`;
            dots.forEach((dot, i) => {
                const active = i === current;
                dot.classList.toggle('bg-white', active);
                dot.classList.toggle('bg-white/60', !active);
            });
        };

        next?.addEventListener('click', () => {
            current = (current + 1) % slides.length;
            update();
        });

        prev?.addEventListener('click', () => {
            current = (current - 1 + slides.length) % slides.length;
            update();
        });

        dots.forEach((dot, i) => {
            dot.addEventListener('click', () => {
                current = i;
                update();
            });
        });

        let startX = null;
        carousel.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
        }, { passive: true });
        carousel.addEventListener('touchend', (e) => {
            if (startX === null) {
                return;
            }
            const delta = e.changedTouches[0].clientX - startX;
            if (delta > 40) {
                current = (current - 1 + slides.length) % slides.length;
                update();
            } else if (delta < -40) {
                current = (current + 1) % slides.length;
                update();
            }
            startX = null;
        }, { passive: true });

        update();
    });
}

function initBarcodeScanner(button) {
    if (!('BarcodeDetector' in window)) {
        button.hidden = true;

        return;
    }

    button.addEventListener('click', async () => {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
        } catch (error) {
            alert('Не удалось получить доступ к камере.');
            return;
        }

        const detector = new BarcodeDetector({ formats: ['ean_13', 'ean_8', 'upc_a', 'upc_e', 'code_128'] });
        const video = document.createElement('video');
        video.setAttribute('playsinline', '');
        video.autoplay = true;
        video.muted = true;

        const overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 z-50 bg-black flex flex-col items-center justify-center p-4';
        overlay.innerHTML = `
            <p class="text-white mb-3 text-sm">Наведите камеру на штрихкод</p>
            <button type="button" class="mb-3 rounded-lg bg-white px-4 py-2 text-gray-800" id="scan-cancel">Отмена</button>
        `;
        overlay.prepend(video);
        document.body.appendChild(overlay);

        const stop = () => {
            try {
                stream.getTracks().forEach((track) => track.stop());
            } catch (e) {
                // ignore
            }
            overlay.remove();
        };

        overlay.querySelector('#scan-cancel').addEventListener('click', stop);

        video.srcObject = stream;

        const scanLoop = async () => {
            if (!overlay.isConnected) {
                return;
            }

            try {
                const codes = await detector.detect(video);
                if (codes.length > 0) {
                    const barcode = codes[0].rawValue;
                    stop();
                    window.location.href = `/products?search=${encodeURIComponent(barcode)}&barcode=1`;
                    return;
                }
            } catch (e) {
                // detection errors are expected while video starts
            }

            requestAnimationFrame(scanLoop);
        };

        video.onloadedmetadata = () => {
            video.play().then(scanLoop).catch(stop);
        };
    });
}