import { reactive } from 'vue'

/**
 * Изолированный словарь Doxa.
 * Статика хоста: /doxa/dictionary/{lang}.json (не /dictionary хоста).
 */
const dictionary = reactive({
    vocabulary: {},
    text_blocks: {},
    ready: false,
})

/**
 * Грузит /doxa/dictionary/{lang}.json.
 * Нет файла (404) или битый JSON — бросает, ready не ставит.
 */
export async function loadDictionary(lang) {
    const res = await fetch(`/doxa/dictionary/${lang}.json?t=${Date.now()}`)
    if (!res.ok) {
        throw new Error(`Doxa dictionary not found: /doxa/dictionary/${lang}.json (${res.status})`)
    }

    const data = await res.json()
    if (!data || typeof data !== 'object') {
        throw new Error(`Doxa dictionary invalid JSON: /doxa/dictionary/${lang}.json`)
    }

    dictionary.vocabulary = data.vocabulary || {}
    dictionary.text_blocks = data.text_blocks || {}
    dictionary.ready = true
}

const namespaceMap = {
    vcb: 'vocabulary',
    txt: 'text_blocks',
}

/**
 * Возвращает строку перевода по ключу vcb.* / txt.*.
 */
export function vocab(key, variables = {}) {
    const firstDot = key.indexOf('.')
    if (firstDot === -1) {
        return `[${key}]`
    }
    const shortNs = key.slice(0, firstDot)
    const actualKey = key.slice(firstDot + 1)
    const fullNs = namespaceMap[shortNs]
    if (!fullNs) {
        return `[${key}]`
    }
    let text = dictionary?.[fullNs]?.[actualKey] || `[${key}]`

    if (variables && Object.keys(variables).length > 0) {
        Object.keys(variables).forEach((varKey) => {
            const value = variables[varKey]
            text = text.replace(new RegExp(`:${varKey}(?![\\w:])`, 'g'), value)
            text = text.replace(new RegExp(`\\{\\{${varKey}\\}\\}`, 'g'), value)
            text = text.replace(new RegExp(`\\{${varKey}\\}`, 'g'), value)
        })
    }

    return text
}

export function isDictionaryReady() {
    return dictionary.ready
}
