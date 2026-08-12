import axios from 'axios';

const APP_API_URL = import.meta.env.VITE_APP_API_URL;
const OAUTH_CALLBACK_URL = import.meta.env.VITE_APP_OAUTH_CALLBACK_URL;

export async function apiOAuthRedirect(driver) {
  try {
    return await axios.get(APP_API_URL + `/${driver}/oauth/redirect`, { params: { callback_url: OAUTH_CALLBACK_URL } });
  } catch (error) {
    throw error;
  }
}
export async function apiOAuthExchangeToken(token) {
  try {
    return await axios.post(APP_API_URL + '/oauth/exchange/token', null, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });
  } catch (error) {
    throw error;
  }
}
