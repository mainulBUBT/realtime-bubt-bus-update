export const DEFAULT_APP_NAMES = {
  driver: 'BUBT Driver',
  student: 'BUBT Tracker'
}

export const DEFAULT_APP_PRIMARY_COLORS = {
  driver: '#10B981',
  student: '#10B981'
}

export const DEFAULT_APP_TAGLINES = {
  driver: 'Campus Shuttle Driver App',
  student: 'Your Campus Shuttle Companion'
}

export function getDefaultAppName(appType = 'student') {
  return DEFAULT_APP_NAMES[appType] || DEFAULT_APP_NAMES.student
}

export function getDefaultAppPrimaryColor(appType = 'student') {
  return DEFAULT_APP_PRIMARY_COLORS[appType] || DEFAULT_APP_PRIMARY_COLORS.student
}

export function getDefaultAppTagline(appType = 'student') {
  return DEFAULT_APP_TAGLINES[appType] || DEFAULT_APP_TAGLINES.student
}
