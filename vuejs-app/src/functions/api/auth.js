import axios from 'axios';

const APP_API_URL = import.meta.env.VITE_APP_API_URL;

export async function apiSignUp(user) {
  return await axios.post(APP_API_URL + '/signup', user);
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
