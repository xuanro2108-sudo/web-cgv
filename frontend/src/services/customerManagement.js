import { useEffect, useState } from "react";

const baseUrl = (import.meta.env.VITE_API_URL || "http://127.0.0.1:8000/api").replace(/\/$/, "");

export async function customerRequest(path, options = {}) {
  const response = await fetch(`${baseUrl}/quan-ly/khach-hangs${path}`, {
    ...options,
    headers: { Accept: "application/json", "Content-Type": "application/json", Authorization: `Bearer ${localStorage.getItem("token") || ""}` },
  });
  const body = await response.json().catch(() => null);
  if (!response.ok) {
    if (response.status === 401) {
      throw new Error("Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.");
    }
    if (response.status === 403) {
      throw new Error("Tài khoản không có quyền quản lý khách hàng hoặc đã bị khóa.");
    }
    throw new Error(body?.message || "Không thể thực hiện yêu cầu. Vui lòng thử lại.");
  }
  if (!body) throw new Error("Máy chủ trả về dữ liệu không hợp lệ.");
  return body;
}

export function useCustomerData(path, revision = 0) {
  const [result, setResult] = useState(null);
  const key = `${path}:${revision}`;
  useEffect(() => {
    const controller = new AbortController();
    let active = true;
    const timer = setTimeout(() => {
      customerRequest(path, { signal: controller.signal })
        .then((data) => { if (active) setResult({ key, data }); })
        .catch((error) => { if (active) setResult({ key, error: error.message === "Failed to fetch" ? "Không thể kết nối máy chủ. Kiểm tra kết nối và thử lại." : error.message }); });
    }, 250);
    return () => { active = false; clearTimeout(timer); controller.abort(); };
  }, [path, key]);
  return result?.key === key ? { ...result, loading: false } : { loading: true };
}
