function generateFingerprint() {
    const components = [];
    

    components.push(navigator.userAgent || '');
    

    components.push(`${screen.width}x${screen.height}`);
    

    try {
        components.push(Intl.DateTimeFormat().resolvedOptions().timeZone || '');
    } catch (e) {
        components.push('');
    }
    

    components.push(navigator.language || navigator.userLanguage || '');
    

    try {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        canvas.width = 200;
        canvas.height = 50;
        ctx.textBaseline = 'top';
        ctx.font = '14px Arial';
        ctx.fillText('Fingerprint', 2, 2);
        const canvasData = canvas.toDataURL();

        let hash = 0;
        for (let i = 0; i < canvasData.length; i++) {
            hash = ((hash << 5) - hash) + canvasData.charCodeAt(i);
            hash = hash & hash; 
        }
        components.push(hash.toString(36));
    } catch (e) {
        components.push('');
    }
    

    components.push(screen.colorDepth || '');
    

    components.push(window.devicePixelRatio || 1);
    

    const combined = components.join('|');
    

    let hash1 = 0;
    let hash2 = 0;
    for (let i = 0; i < combined.length; i++) {
        const char = combined.charCodeAt(i);
        hash1 = ((hash1 << 5) - hash1) + char;
        hash1 = hash1 & hash1; 
        hash2 = ((hash2 << 3) - hash2) + char + i;
        hash2 = hash2 & hash2;
    }
    

    const hashStr = Math.abs(hash1).toString(36) + Math.abs(hash2).toString(36);
    

    if (hashStr.length < 10) {

        const timestampHash = Math.abs(Date.now()).toString(36).substring(0, 10 - hashStr.length);
        return (hashStr + timestampHash).substring(0, 10);
    }
    
    return hashStr.substring(0, 32); 
}

window.generateFingerprint = generateFingerprint;
