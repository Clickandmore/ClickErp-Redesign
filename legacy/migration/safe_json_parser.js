/**
 * دوال معالجة JSON آمنة لتجنب مشاكل warnings
 */

function cleanAndParseJSON(text) {
    console.log('Original response:', text);

    // تنظيف warnings من MySQL
    let cleanText = text;

    // إزالة warnings شائعة
    cleanText = cleanText.replace(/mysql: \[Warning\][^\n]*/g, '');
    cleanText = cleanText.replace(/Warning: Using a password[^\n]*/g, '');
    cleanText = cleanText.replace(/Warning: [^\n]*/g, '');

    // إزالة PHP warnings/notices
    cleanText = cleanText.replace(/<br\s*\/?>/gi, '');
    cleanText = cleanText.replace(/<b>Warning<\/b>:[^<]*<br\s*\/?>/gi, '');
    cleanText = cleanText.replace(/<b>Notice<\/b>:[^<]*<br\s*\/?>/gi, '');
    cleanText = cleanText.replace(/<b>.*?<\/b>/gi, '');

    // إزالة HTML tags
    cleanText = cleanText.replace(/<[^>]*>/g, '');

    // البحث عن JSON صالح
    const jsonMatch = cleanText.match(/\{[\s\S]*\}/);
    if (jsonMatch) {
        cleanText = jsonMatch[0];
    } else {
        // البحث عن بداية ونهاية JSON
        const startIndex = cleanText.indexOf('{');
        const endIndex = cleanText.lastIndexOf('}');

        if (startIndex !== -1 && endIndex !== -1 && endIndex > startIndex) {
            cleanText = cleanText.substring(startIndex, endIndex + 1);
        }
    }

    cleanText = cleanText.trim();
    console.log('Cleaned text:', cleanText);

    try {
        return JSON.parse(cleanText);
    } catch (e) {
        console.error('JSON Parse Error:', e);
        console.error('Failed text:', cleanText);
        throw new Error('خطأ في تحليل استجابة الخادم - راجع Console للتفاصيل');
    }
}

async function safeFetch(url, options = {}) {
    try {
        const response = await fetch(url, options);

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const text = await response.text();
        return cleanAndParseJSON(text);

    } catch (error) {
        if (error.message.includes('JSON')) {
            throw error;
        } else {
            throw new Error(`خطأ في الشبكة: ${error.message}`);
        }
    }
}

// إضافة الدوال للـ window object
window.cleanAndParseJSON = cleanAndParseJSON;
window.safeFetch = safeFetch;
