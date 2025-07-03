import { makeHttpReq } from "../../../../helper/makeHttpReq";

export async function getAllUsers() {
  // Gọi API lấy tất cả user (bạn cần có API này ở backend, ví dụ /api/users/all)
  const res = await makeHttpReq<undefined, any>("users/all", "GET");
  return res.data || [];
}
