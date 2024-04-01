/*!
 * Color mode toggler for Bootstrap's docs (https://getbootstrap.com/)
 * Copyright 2011-2024 The Bootstrap Authors
 * Licensed under the Creative Commons Attribution 3.0 Unported License.
 * Modified to work with a simpler checkbox toggle
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

  const setTheme = theme => {
    if (theme === 'auto') {
      document.documentElement.setAttribute('data-bs-theme', getPreferredTheme())
    } else {
      document.documentElement.setAttribute('data-bs-theme', theme)
    }
  }

  setTheme(getStoredPreferredTheme())

  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    const storedTheme = getStoredTheme()
    if (storedTheme !== 'light' && storedTheme !== 'dark') {
      setTheme(getStoredPreferredTheme())
    }
  })

  window.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('#bd-theme')
    toggle.checked = getStoredTheme() === 'dark'
    toggle.addEventListener('change', (event) => {
      const theme = event.currentTarget.checked ? 'dark' : 'light'
      setStoredTheme(theme)
      setTheme(theme)
      event.currentTarget.focus()
    })
  })
})()
