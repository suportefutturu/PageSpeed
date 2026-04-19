declare global {
  interface Window {
    ScriptStyleManagerSettings: { nonce: string; apiUrl: string };
  }
}
export const apiUrl = window?.ScriptStyleManagerSettings?.apiUrl;
export const nonce = window?.ScriptStyleManagerSettings?.nonce;
