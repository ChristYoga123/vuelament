import { computed, watch, ref } from 'vue'

/**
 * useFormReactivity — evaluate reactivity rules client-side.
 *
 * No server requests needed for show/hide/disable/required.
 * Rules are defined in PHP via HasReactivity trait and
 * serialized as JSON inside the form schema.
 *
 * @param {import('vue').Ref<Object>} formData - reactive form data object
 */
export function useFormReactivity(formData) {
  // Track which fields are loading (for dependent selects, etc.)
  const loadingFields = ref(new Set())

  /**
   * Evaluate a single rule against current formData.
   */
  const evaluateSingleRule = (rule) => {
    if (!rule || !rule.field) return true

    const fieldValue = formData.value
      ? formData.value[rule.field]
      : formData[rule.field]

    switch (rule.operator) {
      case '===':
      case '=':
        return fieldValue === rule.value ||
               String(fieldValue) === String(rule.value)
      case '!==':
      case '!=':
        return fieldValue !== rule.value &&
               String(fieldValue) !== String(rule.value)
      case 'in':
        return Array.isArray(rule.value) && rule.value.includes(fieldValue)
      case 'notIn':
        return Array.isArray(rule.value) && !rule.value.includes(fieldValue)
      case 'filled':
        return fieldValue !== null &&
               fieldValue !== '' &&
               fieldValue !== undefined &&
               fieldValue !== 0 &&
               fieldValue !== false
      case 'blank':
        return fieldValue === null ||
               fieldValue === '' ||
               fieldValue === undefined
      case '>':
        return Number(fieldValue) > Number(rule.value)
      case '<':
        return Number(fieldValue) < Number(rule.value)
      case '>=':
        return Number(fieldValue) >= Number(rule.value)
      case '<=':
        return Number(fieldValue) <= Number(rule.value)
      default:
        return true
    }
  }

  /**
   * Evaluate a rule which might be a simple rule or a compound (and/or) rule.
   */
  const evaluateRule = (rule) => {
    if (!rule) return true

    // Compound rule with logic: 'and' | 'or'
    if (rule.logic && Array.isArray(rule.rules)) {
      if (rule.logic === 'and') {
        return rule.rules.every(evaluateSingleRule)
      }
      if (rule.logic === 'or') {
        return rule.rules.some(evaluateSingleRule)
      }
    }

    // Simple rule
    return evaluateSingleRule(rule)
  }

  /**
   * Check if component should be visible.
   * - If reactivity.visible is set → show only if rule evaluates to true
   * - If reactivity.hidden is set → hide if rule evaluates to true
   * - Also respects legacy conditions[] array
   */
  const isVisible = (comp) => {
    // Server-side hidden flag
    if (comp.hidden === true) return false

    // Reactivity visible rule
    if (comp.reactivity?.visible) {
      return evaluateRule(comp.reactivity.visible)
    }

    // Reactivity hidden rule (inverted)
    if (comp.reactivity?.hidden) {
      return !evaluateRule(comp.reactivity.hidden)
    }

    // Legacy conditions (AND logic — all must match)
    if (comp.conditions?.length) {
      return comp.conditions.every(cond => evaluateSingleRule({
        field: cond.field,
        operator: cond.operator || '===',
        value: cond.value,
      }))
    }

    return true
  }

  /**
   * Check if component should be disabled.
   */
  const isDisabled = (comp) => {
    // Server-side disabled flag
    if (comp.disabled === true) return true

    // Loading state
    if (loadingFields.value.has(comp.name)) return true

    // Reactivity disabled rule
    if (comp.reactivity?.disabled) {
      return evaluateRule(comp.reactivity.disabled)
    }

    // Reactivity enabled rule (inverted)
    if (comp.reactivity?.enabled) {
      return !evaluateRule(comp.reactivity.enabled)
    }

    return false
  }

  /**
   * Check if component is required.
   */
  const isRequired = (comp) => {
    // Reactivity required rule
    if (comp.reactivity?.required) {
      return evaluateRule(comp.reactivity.required)
    }

    // Fallback to static required
    return comp.required || false
  }

  /**
   * Set loading state for a field (e.g., dependent select loading options).
   */
  const setFieldLoading = (fieldName, loading = true) => {
    if (loading) {
      loadingFields.value.add(fieldName)
    } else {
      loadingFields.value.delete(fieldName)
    }
  }

  return {
    isVisible,
    isDisabled,
    isRequired,
    loadingFields,
    setFieldLoading,
    evaluateRule,
  }
}
