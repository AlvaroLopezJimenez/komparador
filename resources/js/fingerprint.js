/**
 * Genera un fingerprint ligero del navegador
 * Usa: User-agent, resolución, timezone, idioma, capacidades gráficas básicas
 */
function generateFingerprint() {
    const components = [];
    
    // 1. User-agent
    components.push(navigator.userAgent || '');
    
    // 2. Resolución de pantalla
    components.push(`${screen.width}x${screen.height}`);
    
    // 3. Timezone
    try {
        components.push(Intl.DateTimeFormat().resolvedOptions().timeZone || '');
    } catch (e) {
        components.push('');
    }
    
    // 4. Idioma
    components.push(navigator.language || navigator.userLanguage || '');
    
    // 5. Capacidades gráficas básicas (canvas fingerprint)
    try {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        canvas.width = 200;
        canvas.height = 50;
        ctx.textBaseline = 'top';
        ctx.font = '14px Arial';
        ctx.fillText('Fingerprint', 2, 2);
        const canvasData = canvas.toDataURL();
        // Hash simple del canvas
        let hash = 0;
        for (let i = 0; i < canvasData.length; i++) {
            hash = ((hash << 5) - hash) + canvasData.charCodeAt(i);
            hash = hash & hash; // Convert to 32bit integer
        }
        components.push(hash.toString(36));
    } catch (e) {
        components.push('');
    }
    
    // 6. Color depth
    components.push(screen.colorDepth || '');
    
    // 7. Pixel ratio
    components.push(window.devicePixelRatio || 1);
    
    // Combinar y crear hash
    const combined = components.join('|');
    
    // Hash simple pero efectivo - usar múltiples pasadas para asegurar longitud
    let hash1 = 0;
    let hash2 = 0;
    for (let i = 0; i < combined.length; i++) {
        const char = combined.charCodeAt(i);
        hash1 = ((hash1 << 5) - hash1) + char;
        hash1 = hash1 & hash1; // Convert to 32bit integer
        hash2 = ((hash2 << 3) - hash2) + char + i;
        hash2 = hash2 & hash2;
    }
    
    // Combinar ambos hashes y asegurar longitud mínima
    const hashStr = Math.abs(hash1).toString(36) + Math.abs(hash2).toString(36);
    
    // Asegurar que tenga al menos 10 caracteres (padding si es necesario)
    if (hashStr.length < 10) {
        // Usar hash adicional basado en timestamp para padding
        const timestampHash = Math.abs(Date.now()).toString(36).substring(0, 10 - hashStr.length);
        return (hashStr + timestampHash).substring(0, 10);
    }
    
    return hashStr.substring(0, 32); // Limitar a 32 caracteres máximo
}

// Exportar para uso global
window.generateFingerprint = generateFingerprint;

