/**
 * Vishal Web Studio - HTML5 Canvas Signature Pad & Digital Signature Engine
 */

class SignaturePadEngine {
    constructor(canvasId, options = {}) {
        this.canvas = document.getElementById(canvasId);
        if (!this.canvas) return;

        this.ctx = this.canvas.getContext('2d');
        this.isDrawing = false;
        this.strokeColor = options.strokeColor || '#1e3a8a';
        this.lineWidth = options.lineWidth || 2.5;
        this.points = [];

        this.resizeCanvas();
        this.bindEvents();
    }

    resizeCanvas() {
        const rect = this.canvas.getBoundingClientRect();
        const dpr = window.devicePixelRatio || 1;
        this.canvas.width = rect.width * dpr;
        this.canvas.height = (rect.height || 180) * dpr;
        this.ctx.scale(dpr, dpr);
        this.ctx.lineCap = 'round';
        this.ctx.lineJoin = 'round';
        this.ctx.strokeStyle = this.strokeColor;
        this.ctx.lineWidth = this.lineWidth;
    }

    getPos(e) {
        const rect = this.canvas.getBoundingClientRect();
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;
        return {
            x: clientX - rect.left,
            y: clientY - rect.top
        };
    }

    bindEvents() {
        // Pointer / Mouse events
        this.canvas.addEventListener('mousedown', (e) => this.start(e));
        this.canvas.addEventListener('mousemove', (e) => this.draw(e));
        window.addEventListener('mouseup', () => this.stop());

        // Touch events
        this.canvas.addEventListener('touchstart', (e) => {
            e.preventDefault();
            this.start(e);
        }, { passive: false });

        this.canvas.addEventListener('touchmove', (e) => {
            e.preventDefault();
            this.draw(e);
        }, { passive: false });

        window.addEventListener('touchend', () => this.stop());
        window.addEventListener('resize', () => this.resizeCanvas());
    }

    start(e) {
        this.isDrawing = true;
        const pos = this.getPos(e);
        this.ctx.beginPath();
        this.ctx.moveTo(pos.x, pos.y);
        this.points = [pos];
    }

    draw(e) {
        if (!this.isDrawing) return;
        const pos = this.getPos(e);
        this.points.push(pos);

        if (this.points.length > 2) {
            const lastTwo = this.points.slice(-2);
            const controlPoint = lastTwo[0];
            const endPoint = {
                x: (lastTwo[0].x + lastTwo[1].x) / 2,
                y: (lastTwo[0].y + lastTwo[1].y) / 2
            };
            this.ctx.quadraticCurveTo(controlPoint.x, controlPoint.y, endPoint.x, endPoint.y);
            this.ctx.stroke();
        }
    }

    stop() {
        if (this.isDrawing) {
            this.isDrawing = false;
            this.ctx.closePath();
        }
    }

    clear() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        this.points = [];
    }

    isEmpty() {
        return this.points.length === 0;
    }

    toDataURL(format = 'image/png') {
        return this.canvas.toDataURL(format);
    }
}

// Generate stylized SVG from typed name
function generateTypedSignatureSvg(name) {
    const safeName = name.replace(/</g, "&lt;").replace(/>/g, "&gt;");
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="400" height="120" viewBox="0 0 400 120">
        <rect width="100%" height="100%" fill="#ffffff"/>
        <text x="20" y="70" font-family="'Brush Script MT', 'Dancing Script', 'Pacifico', cursive, sans-serif" font-size="36" fill="#1e3a8a">${safeName}</text>
        <line x1="20" y1="85" x2="360" y2="85" stroke="#2563eb" stroke-width="1.5" stroke-dasharray="4,4"/>
        <text x="20" y="105" font-family="Arial, sans-serif" font-size="10" fill="#64748b">Verified Digitally Signed • ${new Date().toLocaleDateString()}</text>
    </svg>`;
    return 'data:image/svg+xml;utf8,' + encodeURIComponent(svg);
}
