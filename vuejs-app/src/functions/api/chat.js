import axios from "axios";

const APP_API_URL = import.meta.env.VITE_APP_API_URL;

export async function apiGetChats(params = {}) {
  return await axios.get(APP_API_URL + "/chats", { params });
}

export async function apiGetChatUsers(params = {}) {
  return await axios.get(APP_API_URL + "/chats/users", { params });
}

export async function apiCreatePersonalChat(userId) {
  return await axios.post(APP_API_URL + `/chats/personal/create`, {
    user_id: userId,
  });
}

export async function apiCreateGroupChat(data) {
  const formData = new FormData();
  Object.keys(data).forEach((key) => {
    if (!data[key]) return;
    formData.append(key, data[key]);
  });
  return await axios.post(APP_API_URL + "/chats/group/create", formData);
}

export async function apiReadChat(chatId) {
  return await axios.get(APP_API_URL + `/chats/read/${chatId}`);
}

export async function apiDeleteChat(chatId) {
  return await axios.delete(APP_API_URL + `/chats/delete/${chatId}`);
}

export async function apiUpdateGroupChat(chatId, data) {
  const formData = new FormData();
  Object.keys(data).forEach((key) => {
    // Allow explicit null values for avatar deletion
    if (data[key] === null) {
      formData.append(key, "");
      return;
    }
    if (!data[key]) return;
    formData.append(key, data[key]);
  });
  return await axios.put(
    APP_API_URL + `/chats/group/update/${chatId}`,
    formData,
  );
}

export async function apiLeaveGroupChat(chatId) {
  return await axios.delete(APP_API_URL + `/chats/group/leave/${chatId}`);
}
