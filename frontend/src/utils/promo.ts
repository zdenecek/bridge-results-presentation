import { Promo } from '@/api/PromoApi';
import { Lang } from '@/i18n';
import { localName } from '@/i18n/names';

/** The promo's texts in the visitor's language, Czech where no English is given. */
export function promoTexts(promo: Promo, lang: Lang) {
    const en = lang === 'en';
    return {
        title: localName(lang, promo.title, promo.titleEn),
        text: (en && promo.textEn) || promo.text,
        facts: ((en && promo.factsEn) || promo.facts || '').split(';').map((f) => f.trim()).filter(Boolean),
        linkLabel: (en ? promo.linkLabelEn : promo.linkLabel) || null,
        deadline: formatDeadline(promo.closes, lang),
    };
}

/** The closing time as written, in Prague time, so visitors abroad see the same clock as the club. */
function formatDeadline(closes: string | null, lang: Lang): string {
    if (!closes || !/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/.test(closes)) return '';
    return new Intl.DateTimeFormat(lang === 'en' ? 'en-GB' : 'cs-CZ', {
        weekday: 'short', day: 'numeric', month: lang === 'en' ? 'short' : 'numeric',
        hour: '2-digit', minute: '2-digit', timeZone: 'UTC',
    }).format(new Date(closes + ':00Z'));
}
