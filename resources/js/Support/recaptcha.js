let recaptchaScriptPromise = null

const getSiteKey = () => window.googleRecaptchaSiteKey || ''

const loadRecaptchaScript = (siteKey) => {
  if (window.grecaptcha?.execute) {
    return Promise.resolve(window.grecaptcha)
  }

  if (recaptchaScriptPromise) {
    return recaptchaScriptPromise
  }

  recaptchaScriptPromise = new Promise((resolve, reject) => {
    const existingScript = document.querySelector('script[data-recaptcha-script]')

    if (existingScript) {
      existingScript.addEventListener('load', () => resolve(window.grecaptcha), { once: true })
      existingScript.addEventListener('error', () => reject(new Error('reCAPTCHA failed to load')), { once: true })
      return
    }

    const script = document.createElement('script')
    script.src = `https://www.google.com/recaptcha/api.js?render=${encodeURIComponent(siteKey)}`
    script.async = true
    script.defer = true
    script.dataset.recaptchaScript = 'true'
    script.onload = () => resolve(window.grecaptcha)
    script.onerror = () => reject(new Error('reCAPTCHA failed to load'))

    document.head.appendChild(script)
  })

  return recaptchaScriptPromise
}

export const getRecaptchaToken = async (action) => {
  const siteKey = getSiteKey()

  if (!siteKey) {
    throw new Error('Missing reCAPTCHA site key')
  }

  const grecaptcha = await loadRecaptchaScript(siteKey)

  if (!grecaptcha?.execute) {
    throw new Error('reCAPTCHA unavailable')
  }

  return new Promise((resolve, reject) => {
    grecaptcha.ready(() => {
      grecaptcha.execute(siteKey, { action })
        .then(resolve)
        .catch(reject)
    })
  })
}
