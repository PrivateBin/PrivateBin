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

  const delStoredPrettifyTheme = () => localStorage.removeItem('themePrettify')
  const getStoredPrettifyTheme = () => localStorage.getItem('themePrettify')
  const setStoredPrettifyTheme = theme => localStorage.setItem('themePrettify', theme)

  const getPrettifyThemeLink = () => {
    for (const sheet of document.getElementsByTagName('link')) {
      if (sheet.rel === 'stylesheet' && sheet.href.includes('css/prettify/') && !sheet.href.includes('css/prettify/prettify.css')) {
        return sheet
      }
    }
    return null
  }

  const setTheme = theme => {
    const preferredTheme = theme === 'auto' ? getPreferredTheme() : theme
    document.documentElement.setAttribute('data-bs-theme', preferredTheme)
    const sheetPrettify = getPrettifyThemeLink()
    if (sheetPrettify) {
      sheetPrettify.remove()
    }
    const link = document.createElement('link')
    link.rel = 'stylesheet'
    if (preferredTheme === 'dark') {
      link.href = 'css/prettify/sons-of-obsidian.css'
      document.head.appendChild(link)
    } else {
      const themePrettify = getStoredPrettifyTheme()
      if (themePrettify) {
        link.href = themePrettify
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
    const sheetPrettify = getPrettifyThemeLink()
    if (sheetPrettify) {
      setStoredPrettifyTheme(sheetPrettify.href)
    } else {
      delStoredPrettifyTheme()
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
