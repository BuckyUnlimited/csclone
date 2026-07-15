import axios from 'axios';

const APP_API_URL = import.meta.env.VITE_APP_API_URL;
const APP_VERIFY_EMAIL_URL = import.meta.env.VITE_APP_VERIFY_EMAIL_URL;

export async function apiSignUp(user) {
  return await axios.post(APP_API_URL + '/signup', {
    ...user,
    callback_url: APP_VERIFY_EMAIL_URL,
  });
}
export async function apiSignIn(user) {
  return await axios.post(APP_API_URL + '/signin', user);
}
export async function apiSignOut(token) {
  return await axios.post(APP_API_URL + '/signout', null, {
    headers: {
      Authorization: `Bearer ${token}`
    }
  });
}
export async function apiVerify(token) {
  return await axios.get(APP_API_URL + '/verify', {
    headers: {
      Authorization: `Bearer ${token}`
    }
  });
}
export async function apiSendVerificationEmail(email) {
  return await axios.post(APP_API_URL + '/send/verification-email', { email, callback_url: APP_VERIFY_EMAIL_URL });
}
