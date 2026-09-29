/*
  The rows behind the landing page demo. Imported by both build.mjs (to render the static table,
  so the sheet still reads correctly without JavaScript) and landing.js (to animate it).

  The columns match what the package actually writes: File | Key | Tag | EN | <TARGET>
  (see GoogleSheetsService::updateHeaders), and the source text is column D, which is what the
  =GOOGLETRANSLATE() formula points at.
*/

export const SOURCE = 'en';

export const LOCALES = [
  { code: 'fr', label: 'fr', name: 'French' },
  { code: 'es', label: 'es', name: 'Spanish' },
  { code: 'de', label: 'de', name: 'German' },
  { code: 'ne', label: 'ne', name: 'Nepali' },
  { code: 'ja', label: 'ja', name: 'Japanese' },
];

export const ROWS = [
  {
    file: 'auth.php',
    key: 'failed',
    en: 'These credentials do not match our records.',
    t: {
      fr: 'Ces identifiants ne correspondent pas à nos enregistrements.',
      es: 'Estas credenciales no coinciden con nuestros registros.',
      de: 'Diese Zugangsdaten stimmen nicht mit unseren Aufzeichnungen überein.',
      ne: 'यी प्रमाणहरू हाम्रो अभिलेखसँग मेल खाँदैनन्।',
      ja: '認証情報が記録と一致しません。',
    },
  },
  {
    file: 'auth.php',
    key: 'password',
    en: 'The provided password is incorrect.',
    t: {
      fr: 'Le mot de passe fourni est incorrect.',
      es: 'La contraseña proporcionada es incorrecta.',
      de: 'Das angegebene Passwort ist nicht korrekt.',
      ne: 'प्रविष्ट गरिएको पासवर्ड गलत छ।',
      ja: '入力されたパスワードが正しくありません。',
    },
  },
  {
    file: 'auth.php',
    key: 'throttle',
    en: 'Too many login attempts. Please try again in :seconds seconds.',
    t: {
      fr: 'Trop de tentatives de connexion. Veuillez réessayer dans :seconds secondes.',
      es: 'Demasiados intentos de acceso. Inténtelo de nuevo en :seconds segundos.',
      de: 'Zu viele Anmeldeversuche. Bitte versuchen Sie es in :seconds Sekunden erneut.',
      ne: 'धेरै पटक लगइन प्रयास भयो। कृपया :seconds सेकेन्डपछि पुनः प्रयास गर्नुहोस्।',
      ja: 'ログイン試行回数が多すぎます。:seconds 秒後にもう一度お試しください。',
    },
  },
  {
    file: 'passwords.php',
    key: 'sent',
    en: 'We have emailed your password reset link.',
    t: {
      fr: 'Nous vous avons envoyé par e-mail le lien de réinitialisation de mot de passe.',
      es: 'Le hemos enviado por correo el enlace para restablecer su contraseña.',
      de: 'Wir haben Ihnen den Link zum Zurücksetzen des Passworts per E-Mail gesendet.',
      ne: 'हामीले तपाईंको पासवर्ड रिसेट लिंक इमेल गरेका छौं।',
      ja: 'パスワードリセットリンクをメールで送信しました。',
    },
  },
  {
    file: 'passwords.php',
    key: 'reset',
    en: 'Your password has been reset.',
    t: {
      fr: 'Votre mot de passe a été réinitialisé.',
      es: 'Su contraseña ha sido restablecida.',
      de: 'Ihr Passwort wurde zurückgesetzt.',
      ne: 'तपाईंको पासवर्ड रिसेट गरियो।',
      ja: 'パスワードがリセットされました。',
    },
  },
];

// A real run pushes every string in lang/en, not just the five rows shown above. These numbers come
// from the run in the dashboard screenshot (auth 3, pagination 2, passwords 5, validation 136).
export const TOTAL_STRINGS = 146;
export const TOTAL_FILES = 4;

/** Laravel placeholders (":seconds"), which are masked before translation and restored after. */
export const PLACEHOLDER_RE = /:([a-zA-Z_]\w*)/g;

/** The files the demo writes back, in the order their rows appear. */
export const FILES = [...new Set(ROWS.map((r) => r.file))];
