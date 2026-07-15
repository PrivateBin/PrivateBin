/*!
 * Color mode toggler for Bootstrap's docs (https://getbootstrap.com/)
 * Copyright 2011-2024 The Bootstrap Authors
 * Licensed under the Creative Commons Attribution 3.0 Unported License.
 * Modified to work with a simpler checkbox toggle & support prettify CSS themes
 */

(() => {
  'use strict'

  const getStoredTheme = () => localStorage.getItem('theme')
  const setStoredTheme = theme => localStorage.setItem('theme', theme)
  const getPreferredTheme = () => window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'

  const getStoredPreferredTheme = () => {
    const storedTheme = getStoredTheme()
    if (storedTheme) {
      return storedTheme
    }
    return getPreferredTheme()
  }

  const delStoredPrismTheme = () => localStorage.removeItem('themePrism')
  const getStoredPrismTheme = () => localStorage.getItem('themePrism')
  const setStoredPrismTheme = theme => localStorage.setItem('themePrism', theme)

  const getPrismThemeLink = () => {
    for (const sheet of document.getElementsByTagName('link')) {
      if (sheet.rel === 'stylesheet' && sheet.href.includes('css/prism/') && !sheet.href.includes('css/prism/prism-1.29.0.css')) {
        return sheet
      }
    }
    return null
  }

  const setTheme = theme => {
    const preferredTheme = theme === 'auto' ? getPreferredTheme() : theme
    document.documentElement.setAttribute('data-bs-theme', preferredTheme)
    const sheetPrism = getPrismThemeLink()
    if (sheetPrism) {
      sheetPrism.remove()
    }
    const link = document.createElement('link')
    link.rel = 'stylesheet'
    if (preferredTheme === 'dark') {
      link.href = 'css/prism/sons-of-obsidian.css'
      document.head.appendChild(link)
    } else {
      const themePrism = getStoredPrismTheme()
      if (themePrism) {
        link.href = themePrism
        document.head.appendChild(link)
      }
    }
  }

  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    const storedTheme = getStoredTheme()
    if (storedTheme !== 'light' && storedTheme !== 'dark') {
      setTheme(getStoredPreferredTheme())
    }
  })

  window.addEventListener('DOMContentLoaded', () => {
    const sheetPrism = getPrismThemeLink()
    if (sheetPrism) {
      setStoredPrismTheme(sheetPrism.href)
    } else {
      delStoredPrismTheme()
    }
    const toggle = document.getElementById('bd-theme')
    const theme = getStoredPreferredTheme()
    setTheme(theme)
    toggle.checked = (theme === 'dark')
    toggle.addEventListener('change', (event) => {
      const theme = event.currentTarget.checked ? 'dark' : 'light'
      setStoredTheme(theme)
      setTheme(theme)
      event.currentTarget.focus()
    })
  })
})()
