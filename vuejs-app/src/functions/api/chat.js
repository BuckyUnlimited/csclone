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

export async function apiGetGroupChatMembers(chatId, params = {}) {
  return await axios.get(APP_API_URL + `/chats/group/${chatId}/members`, {
    params,
  });
}
export async function apiAddGroupChatMember(chatId, userId) {
  return await axios.post(APP_API_URL + `/chats/group/${chatId}/members/add`, {
    user_id: userId,
  });
}
export async function apiRemoveGroupChatMember(chatId, memberId) {
  return await axios.delete(
    APP_API_URL + `/chats/group/${chatId}/members/remove/${memberId}`,
  );
}

export async function apiGetChatMessages(chatId, params = {}) {
  return await axios.get(APP_API_URL + `/chats/${chatId}/messages`, { params });
}

export async function apiCreateChatMessage(chatId, content) {
  return await axios.post(APP_API_URL + `/chats/${chatId}/messages/create`, {
    content,
  });
}

export async function apiUpdateChatMessage(chatId, messageId, content) {
  return await axios.patch(
    APP_API_URL + `/chats/${chatId}/messages/update/${messageId}`,
    {
      content,
    },
  );
}

export async function apiDeleteChatMessage(chatId, messageId) {
  return await axios.delete(
    APP_API_URL + `/chats/${chatId}/messages/delete/${messageId}`,
  );
}

export async function apiMarkAllChatMessagesAsSeen(chatId) {
  return await axios.post(APP_API_URL + `/chats/${chatId}/messages/seen-all`);
}

export async function apiCreateVoiceChatMessage(chatId, voiceBlob) {
  const formData = new FormData();
  formData.append("voice", voiceBlob, "voice.webm");
  return await axios.post(
    APP_API_URL + `/chats/${chatId}/messages/create-voice`,
    formData,
  );
}
