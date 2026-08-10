import axios from 'axios';

const APP_API_URL = import.meta.env.VITE_APP_API_URL;
const APP_VERIFY_EMAIL_URL = import.meta.env.VITE_APP_VERIFY_EMAIL_URL;
const APP_RESET_PASSWORD_URL = import.meta.env.VITE_APP_RESET_PASSWORD_URL;

export async function apiSignUp(user) {
  return await axios.post(APP_API_URL + '/signup', {
    ...user,
    callback_url: APP_VERIFY_EMAIL_URL,
  });
}
export async function apiSignIn(user) {
  return await axios.post(APP_API_URL + '/signin', user);
}
export async function apiSignOut(token) { // !!!!! can not be overwrite by axios interceptor since we need to remove the token before the request is sent
  return await axios.post(APP_API_URL + '/signout', null, {
    headers: {
      Authorization: `Bearer ${token}`
    }
  });
}
export async function apiVerify() { // can be overwrite by axios interceptor
  return await axios.get(APP_API_URL + '/verify');
}
export async function apiSendVerificationEmail(email) {
  return await axios.post(APP_API_URL + '/send/verification-email', { email, callback_url: APP_VERIFY_EMAIL_URL });
}
export async function apiSendResetPasswordEmail(email) {
  return await axios.post(APP_API_URL + '/send/reset-password-email', { email, callback_url: APP_RESET_PASSWORD_URL });
}
export async function apiCreatePassword(new_password, new_password_confirmation) {
  return await axios.put(APP_API_URL + '/create/password', { new_password, new_password_confirmation });
}
export async function apiChangePassword(current_password, new_password, new_password_confirmation) {
  return await axios.put(APP_API_URL + '/change/password', { current_password, new_password, new_password_confirmation });
}
export async function apiUpdateProfileImage(image) {
  const formData = new FormData();
  formData.append('profile_image', image);
  formData.append('_method', 'PUT');
  return await axios.post(APP_API_URL + '/update/profile-image', formData);
}
export async function apiDeleteProfileImage() {
  return await axios.delete(APP_API_URL + '/delete/profile-image');
}
