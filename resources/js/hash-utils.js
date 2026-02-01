/**
 * Hash Utilities for CryptoWAF
 * Provides client-side hashing and cryptographic functions
 */

class HashUtils {
    constructor() {
        this.algorithm = 'SHA-256';
        this.iterations = 100000;
        this.keyLength = 256;
    }

    // Generate SHA-256 hash
    async sha256(data) {
        const encoder = new TextEncoder();
        const buffer = encoder.encode(data);
        const hashBuffer = await crypto.subtle.digest('SHA-256', buffer);
        return this.bufferToHex(hashBuffer);
    }

    // Generate SHA-512 hash
    async sha512(data) {
        const encoder = new TextEncoder();
        const buffer = encoder.encode(data);
        const hashBuffer = await crypto.subtle.digest('SHA-512', buffer);
        return this.bufferToHex(hashBuffer);
    }

    // Generate HMAC
    async hmac(data, key, algorithm = 'SHA-256') {
        const encoder = new TextEncoder();
        const cryptoKey = await crypto.subtle.importKey(
            'raw',
            encoder.encode(key),
            { name: 'HMAC', hash: algorithm },
            false,
            ['sign']
        );
        
        const signature = await crypto.subtle.sign(
            'HMAC',
            cryptoKey,
            encoder.encode(data)
        );
        
        return this.bufferToHex(signature);
    }

    // Generate PBKDF2 key derivation
    async pbkdf2(password, salt, iterations = this.iterations, keyLength = this.keyLength) {
        const encoder = new TextEncoder();
        
        const keyMaterial = await crypto.subtle.importKey(
            'raw',
            encoder.encode(password),
            'PBKDF2',
            false,
            ['deriveBits']
        );
        
        const derivedBits = await crypto.subtle.deriveBits(
            {
                name: 'PBKDF2',
                salt: encoder.encode(salt),
                iterations: iterations,
                hash: 'SHA-256'
            },
            keyMaterial,
            keyLength
        );
        
        return this.bufferToHex(derivedBits);
    }

    // Generate secure random bytes
    generateRandomBytes(length) {
        const array = new Uint8Array(length);
        crypto.getRandomValues(array);
        return Array.from(array, byte => byte.toString(16).padStart(2, '0')).join('');
    }

    // Generate secure password
    generatePassword(length = 16) {
        const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=[]{}|;:,.<>?';
        let password = '';
        const randomValues = new Uint32Array(length);
        crypto.getRandomValues(randomValues);
        
        for (let i = 0; i < length; i++) {
            password += charset[randomValues[i] % charset.length];
        }
        
        return password;
    }

    // Verify password strength
    checkPasswordStrength(password) {
        const checks = {
            length: password.length >= 8,
            lowercase: /[a-z]/.test(password),
            uppercase: /[A-Z]/.test(password),
            numbers: /\d/.test(password),
            special: /[!@#$%^&*()_+\-=\[\]{}|;:,.<>?]/.test(password),
        };
        
        const score = Object.values(checks).filter(Boolean).length;
        let strength = 'Weak';
        
        if (score === 5) strength = 'Very Strong';
        else if (score === 4) strength = 'Strong';
        else if (score === 3) strength = 'Moderate';
        
        return {
            score,
            strength,
            checks,
            suggestions: this.getPasswordSuggestions(checks)
        };
    }

    // Get password suggestions
    getPasswordSuggestions(checks) {
        const suggestions = [];
        
        if (!checks.length) suggestions.push('Use at least 8 characters');
        if (!checks.lowercase) suggestions.push('Add lowercase letters');
        if (!checks.uppercase) suggestions.push('Add uppercase letters');
        if (!checks.numbers) suggestions.push('Add numbers');
        if (!checks.special) suggestions.push('Add special characters');
        
        return suggestions;
    }

    // Convert ArrayBuffer to hex string
    bufferToHex(buffer) {
        return Array.from(new Uint8Array(buffer))
            .map(b => b.toString(16).padStart(2, '0'))
            .join('');
    }

    // Convert hex string to ArrayBuffer
    hexToBuffer(hex) {
        const bytes = new Uint8Array(hex.length / 2);
        for (let i = 0; i < hex.length; i += 2) {
            bytes[i / 2] = parseInt(hex.substr(i, 2), 16);
        }
        return bytes.buffer;
    }

    // Base64 encode
    base64Encode(data) {
        if (typeof data === 'string') {
            return btoa(data);
        } else if (data instanceof ArrayBuffer) {
            const bytes = new Uint8Array(data);
            let binary = '';
            for (let i = 0; i < bytes.byteLength; i++) {
                binary += String.fromCharCode(bytes[i]);
            }
            return btoa(binary);
        }
        return '';
    }

    // Base64 decode
    base64Decode(base64) {
        const binary = atob(base64);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }
        return bytes.buffer;
    }

    // Create data signature
    async createDataSignature(data, secret) {
        // Sort data keys
        const sorted = {};
        Object.keys(data).sort().forEach(key => {
            sorted[key] = data[key];
        });
        
        // Create query string
        const queryString = new URLSearchParams(sorted).toString();
        
        // Create HMAC
        return await this.hmac(queryString, secret);
    }

    // Verify data signature
    async verifyDataSignature(data, signature, secret) {
        const expected = await this.createDataSignature(data, secret);
        return this.secureCompare(expected, signature);
    }

    // Secure string comparison (timing-safe)
    secureCompare(a, b) {
        if (a.length !== b.length) return false;
        
        let result = 0;
        for (let i = 0; i < a.length; i++) {
            result |= a.charCodeAt(i) ^ b.charCodeAt(i);
        }
        return result === 0;
    }

    // Generate UUID v4
    generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    // Generate secure token
    generateToken(length = 32) {
        return this.generateRandomBytes(length);
    }

    // Create challenge-response
    async createChallengeResponse(challenge, secret) {
        return await this.hmac(challenge, secret);
    }

    // Create proof of work
    async createProofOfWork(data, difficulty = 4) {
        let nonce = 0;
        let hash;
        const prefix = '0'.repeat(difficulty);
        
        do {
            const input = data + nonce.toString();
            hash = await this.sha256(input);
            nonce++;
        } while (!hash.startsWith(prefix));
        
        return { nonce: nonce - 1, hash };
    }

    // Verify proof of work
    async verifyProofOfWork(data, nonce, hash, difficulty = 4) {
        const prefix = '0'.repeat(difficulty);
        const testHash = await this.sha256(data + nonce.toString());
        return testHash === hash && hash.startsWith(prefix);
    }
}

// Initialize HashUtils
const hashUtils = new HashUtils();

// Make available globally
window.HashUtils = hashUtils;

// Export for ES6 modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = hashUtils;
}