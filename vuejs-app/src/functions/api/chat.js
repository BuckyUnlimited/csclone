import axios from "axios";

const APP_API_URL = import.meta.env.VITE_APP_API_URL;

export async function apiGetChats(params = {}) {
  return await axios.get(APP_API_URL + "/chats", { params });
}
export async function apiGetChatUsers(params = {}) {
  return await axios.get(APP_API_URL + "/chats/users", { params });
}
