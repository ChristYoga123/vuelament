import { Circle, icons } from 'lucide-vue-next'

/**
 * Resolve a lucide icon name (kebab-case) to its component.
 */
export const resolveIcon = (name) => {
  if (!name) return Circle
  const pascalCase = name.replace(/(^|-)([a-z])/g, (_, __, c) => c.toUpperCase())
  return icons[pascalCase] || Circle
}

/**
 * Format a cell value based on column config (dateFormat, badge, etc).
 */
export const formatCell = (row, col) => {
  let val =
    row._v_columns?.[col.name]?.formatted !== undefined
      ? row._v_columns[col.name].formatted
      : row[col.name]

  if (val === null || val === undefined) return '—'
  if (col.dateFormat && val) {
    try {
      const d = new Date(val)
      return d.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' })
    } catch {
      return val
    }
  }
  if (col.badge) {
    if (typeof val === 'boolean') return val ? 'Ya' : 'Tidak'
    return String(val)
  }
  return String(val)
}

/**
 * Check if a value is truthy (boolean, 1, '1', 'true').
 */
export const isTruthy = (val) => {
  return val === true || val === 1 || val === '1' || val === 'true'
}

/**
 * Resolve a named color to Tailwind class(es).
 * @param {string} color - success | danger | warning | info | custom hex
 * @param {'text'|'badge'} type
 */
export const resolveColorClass = (color, type = 'text') => {
  const badgeMap = {
    success: 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
    danger: 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
    warning: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
    info: 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
  }
  const textMap = {
    success: 'text-green-600 dark:text-green-400',
    danger: 'text-red-600 dark:text-red-400',
    warning: 'text-yellow-600 dark:text-yellow-400',
    info: 'text-blue-600 dark:text-blue-400',
  }
  return (type === 'badge' ? badgeMap : textMap)[color] || ''
}

/**
 * Resolve a named color to inline style string (for custom hex colors).
 */
export const resolveColorStyle = (color, type = 'text') => {
  if (!color) return ''
  if (['success', 'danger', 'warning', 'info'].includes(color)) return ''
  if (type === 'badge') return `background-color: ${color}20; color: ${color}`
  return `color: ${color}`
}

/**
 * Modal max-width class map.
 */
export const maxWidthClass = {
  xs: 'sm:max-w-xs',
  sm: 'sm:max-w-sm',
  md: 'sm:max-w-md',
  lg: 'sm:max-w-lg',
  xl: 'sm:max-w-xl',
  '2xl': 'sm:max-w-2xl',
  '3xl': 'sm:max-w-3xl',
  '4xl': 'sm:max-w-4xl',
  '5xl': 'sm:max-w-5xl',
  '6xl': 'sm:max-w-6xl',
  '7xl': 'sm:max-w-7xl',
  full: 'sm:max-w-full',
}

/**
 * Resolve action button color classes.
 */
export const resolveActionColorClass = (color, defaultColor = null) => {
  const map = {
    danger: 'text-destructive hover:text-destructive',
    warning: 'text-yellow-400 hover:text-yellow-400',
    success: 'text-green-600 hover:text-green-600',
  }
  if (defaultColor && (!color || color === defaultColor)) return map[defaultColor] || ''
  return map[color] || ''
}
