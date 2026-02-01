class CryptoWAF {
    constructor() {
        this.apiBaseUrl = '/api';
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    }

    // Generate cryptographic nonce
    generateNonce() {
        return crypto.getRandomValues(new Uint32Array(4)).join('') + Date.now();
    }

    // Generate timestamp
    getTimestamp() {
        return Math.floor(Date.now() / 1000);
    }

    // Create signature for request
    async createSignature(data, secret) {
        // Sort data keys alphabetically
        const sortedData = {};
        Object.keys(data).sort().forEach(key => {
            sortedData[key] = data[key];
        });

        // Create string to sign
        const stringToSign = new URLSearchParams(sortedData).toString();
        
        // Create HMAC-SHA256 signature
        const encoder = new TextEncoder();
        const key = await crypto.subtle.importKey(
            'raw',
            encoder.encode(secret),
            { name: 'HMAC', hash: 'SHA-256' },
            false,
            ['sign']
        );
        
        const signature = await crypto.subtle.sign(
            'HMAC',
            key,
            encoder.encode(stringToSign)
        );
        
        // Convert to hex
        return Array.from(new Uint8Array(signature))
            .map(b => b.toString(16).padStart(2, '0'))
            .join('');
    }

    // Make signed API request
    async makeSignedRequest(endpoint, method = 'GET', data = {}, apiKey = null, secretKey = null) {
        try {
            const timestamp = this.getTimestamp();
            const nonce = this.generateNonce();
            
            // Prepare request data
            const requestData = {
                ...data,
                timestamp,
                nonce,
                api_key: apiKey
            };
            
            // Create signature
            const signature = await this.createSignature(requestData, secretKey);
            
            // Prepare headers
            const headers = {
                'Content-Type': 'application/json',
                'X-Signature': signature,
                'X-Timestamp': timestamp,
                'X-Nonce': nonce,
                'X-API-Key': apiKey
            };
            
            if (this.csrfToken) {
                headers['X-CSRF-TOKEN'] = this.csrfToken;
            }
            
            // Make request
            const response = await fetch(`${this.apiBaseUrl}${endpoint}`, {
                method,
                headers,
                body: method !== 'GET' ? JSON.stringify(data) : null
            });
            
            if (!response.ok) {
                throw new Error(`API request failed: ${response.statusText}`);
            }
            
            return await response.json();
            
        } catch (error) {
            console.error('Signed request error:', error);
            throw error;
        }
    }

    // Encrypt data in browser (for demo purposes)
    async encryptData(text, password) {
        try {
            // Generate salt
            const salt = crypto.getRandomValues(new Uint8Array(16));
            
            // Derive key from password
            const keyMaterial = await crypto.subtle.importKey(
                'raw',
                new TextEncoder().encode(password),
                'PBKDF2',
                false,
                ['deriveKey']
            );
            
            const key = await crypto.subtle.deriveKey(
                {
                    name: 'PBKDF2',
                    salt: salt,
                    iterations: 100000,
                    hash: 'SHA-256'
                },
                keyMaterial,
                { name: 'AES-GCM', length: 256 },
                false,
                ['encrypt']
            );
            
            // Generate IV
            const iv = crypto.getRandomValues(new Uint8Array(12));
            
            // Encrypt data
            const encrypted = await crypto.subtle.encrypt(
                {
                    name: 'AES-GCM',
                    iv: iv
                },
                key,
                new TextEncoder().encode(text)
            );
            
            // Combine salt, iv, and ciphertext
            const combined = new Uint8Array(salt.length + iv.length + encrypted.byteLength);
            combined.set(salt, 0);
            combined.set(iv, salt.length);
            combined.set(new Uint8Array(encrypted), salt.length + iv.length);
            
            // Convert to base64
            return btoa(String.fromCharCode(...combined));
            
        } catch (error) {
            console.error('Encryption error:', error);
            throw error;
        }
    }

    // Decrypt data in browser
    async decryptData(encryptedBase64, password) {
        try {
            // Convert from base64
            const combined = Uint8Array.from(atob(encryptedBase64), c => c.charCodeAt(0));
            
            // Extract salt, iv, and ciphertext
            const salt = combined.slice(0, 16);
            const iv = combined.slice(16, 28);
            const ciphertext = combined.slice(28);
            
            // Derive key from password
            const keyMaterial = await crypto.subtle.importKey(
                'raw',
                new TextEncoder().encode(password),
                'PBKDF2',
                false,
                ['deriveKey']
            );
            
            const key = await crypto.subtle.deriveKey(
                {
                    name: 'PBKDF2',
                    salt: salt,
                    iterations: 100000,
                    hash: 'SHA-256'
                },
                keyMaterial,
                { name: 'AES-GCM', length: 256 },
                false,
                ['decrypt']
            );
            
            // Decrypt data
            const decrypted = await crypto.subtle.decrypt(
                {
                    name: 'AES-GCM',
                    iv: iv
                },
                key,
                ciphertext
            );
            
            // Convert to text
            return new TextDecoder().decode(decrypted);
            
        } catch (error) {
            console.error('Decryption error:', error);
            throw error;
        }
    }

    // Generate hash of data
    async generateHash(data, algorithm = 'SHA-256') {
        try {
            const encoder = new TextEncoder();
            const hashBuffer = await crypto.subtle.digest(
                algorithm,
                encoder.encode(data)
            );
            
            // Convert to hex
            return Array.from(new Uint8Array(hashBuffer))
                .map(b => b.toString(16).padStart(2, '0'))
                .join('');
        } catch (error) {
            console.error('Hash generation error:', error);
            throw error;
        }
    }

    // Validate input against patterns
    validateInput(input, type = 'general') {
        const patterns = {
            sql: [
                /(\b(SELECT|INSERT|UPDATE|DELETE|DROP|UNION|ALTER|CREATE|EXEC)\b)/i,
                /(\-\-|\#|\/\*)/,
                /(\b(OR|AND)\s+['"]?\d+['"]?\s*=\s*['"]?\d+['"]?)/i
            ],
            xss: [
                /(<script\b[^>]*>|<\/script>)/i,
                /(javascript\s*:|vbscript\s*:|data\s*:)/i,
                /(on\w+\s*=\s*["'][^"']*["'])/i,
                /(expression\s*\(|url\s*\()/i
            ],
            path: [
                /(\.\.\/|\.\.\\|\\\.\.|\/\.\.)/,
                /(\/\/|\|)/,
                /(%00|\\x00|\\u0000)/
            ]
        };
        
        const selectedPatterns = patterns[type] || [];
        
        for (const pattern of selectedPatterns) {
            if (pattern.test(input)) {
                return {
                    valid: false,
                    pattern: pattern.toString(),
                    type: type
                };
            }
        }
        
        return { valid: true };
    }

    // Sanitize input
    sanitizeInput(input, options = {}) {
        let sanitized = input;
        
        // Remove HTML tags
        if (options.stripTags) {
            sanitized = sanitized.replace(/<[^>]*>/g, '');
        }
        
        // Escape special characters
        if (options.escapeHtml) {
            sanitized = sanitized
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#x27;')
                .replace(/\//g, '&#x2F;');
        }
        
        // Remove control characters
        sanitized = sanitized.replace(/[\x00-\x1F\x7F-\x9F]/g, '');
        
        // Trim whitespace
        sanitized = sanitized.trim();
        
        return sanitized;
    }

    // Show notification
    showNotification(message, type = 'info') {
        const alertClass = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info'
        }[type] || 'alert-info';
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert ${alertClass} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const container = document.querySelector('.container') || document.body;
        container.prepend(alertDiv);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }

    // Format bytes to human readable
    formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    // Debounce function
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Throttle function
    throttle(func, limit) {
        let inThrottle;
        return function executedFunction(...args) {
            if (!inThrottle) {
                func(...args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
}

// Initialize CryptoWAF
const cryptoWAF = new CryptoWAF();

// Make available globally
window.CryptoWAF = cryptoWAF;

// Export for ES6 modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = cryptoWAF;
}