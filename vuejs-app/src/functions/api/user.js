import axios from 'axios';

const APP_API_URL = import.meta.env.VITE_APP_API_URL;

export function apiGetUsers(params = {}) {
  return axios.get(APP_API_URL + '/manage/users', { params });
}

export function apiReadUser(id) {
  return axios.get(APP_API_URL + `/manage/users/read/${id}`);
}

export function apiCreateUser(data) {
  return axios.post(APP_API_URL + `/manage/users/create`, data);
}

export function apiUpdateUser(id, data) {
  return axios.put(APP_API_URL + `/manage/users/update/${id}`, data);
}

export function apiToggleUserStatus(id) {
  return axios.patch(APP_API_URL + `/manage/users/toggle-status/${id}`);
}

export function apiDeleteUser(id) {
  return axios.delete(APP_API_URL + `/manage/users/delete/${id}`);
}
