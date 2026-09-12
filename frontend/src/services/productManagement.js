const API = "http://127.0.0.1:8000/api";

async function request(path, options = {}) {
  const response = await fetch(`${API}/${path}`, {
    ...options,
    headers: {
      Accept: "application/json",
      Authorization: `Bearer ${localStorage.getItem("token")}`,
      ...(!(options.body instanceof FormData) ? { "Content-Type": "application/json" } : {}),
    },
  });
  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    const validation = Object.values(data.errors || {}).flat().join(" ");
    throw new Error(validation || (response.status >= 500 ? "Không thể xử lý dữ liệu sản phẩm. Vui lòng thử lại." : data.message) || "Không thể tải dữ liệu sản phẩm.");
  }
  return data;
}

export function getProducts({ keyword, type, status, page }, signal) {
  return request(`quan-ly/san-phams?${new URLSearchParams({ keyword, loaiSP: type, trangThai: status, page })}`, { signal });
}

export function saveProduct(id, data, image) {
  const path = id ? `san-phams/${encodeURIComponent(id)}` : "san-phams";
  if (!image) return request(path, { method: id ? "PUT" : "POST", body: JSON.stringify(data) });
  const body = new FormData();
  for (const [key, value] of Object.entries(data)) {
    if (key !== "hinhAnh") body.append(key, value ?? "");
  }
  body.append("anh", image);
  if (id) body.append("_method", "PUT");
  return request(path, { method: "POST", body });
}

export function stopProduct(id) {
  return request(`san-phams/${encodeURIComponent(id)}`, { method: "DELETE" });
}
