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
    throw new Error(validation || (response.status >= 500 ? "Không thể lưu dữ liệu combo. Vui lòng thử lại." : data.message) || "Không thể tải dữ liệu. Vui lòng thử lại.");
  }
  return data;
}

export function getManagedCombos({ keyword, status, page }, signal) {
  const params = new URLSearchParams({ keyword, trangThai: status, page });
  return request(`quan-ly/combos?${params}`, { signal });
}

export async function getComboProducts(signal) {
  const first = await request("san-phams?page=1", { signal });
  const products = [...first.data];
  for (let page = 2; page <= first.last_page; page += 1) {
    const result = await request(`san-phams?page=${page}`, { signal });
    products.push(...result.data);
  }
  return products;
}

export function saveCombo(id, data, image) {
  const path = id ? `combos/${encodeURIComponent(id)}` : "combos";
  if (!image) return request(path, { method: id ? "PUT" : "POST", body: JSON.stringify(data) });
  const body = new FormData();
  for (const [key, value] of Object.entries(data)) {
    if (key === "hinhAnh") continue;
    if (key === "sanPhams") {
      value.forEach((product, index) => {
        body.append(`sanPhams[${index}][maSP]`, product.maSP);
        body.append(`sanPhams[${index}][soLuong]`, product.soLuong);
      });
    } else body.append(key, value ?? "");
  }
  body.append("anh", image);
  if (id) body.append("_method", "PUT");
  return request(path, { method: "POST", body });
}

export function stopCombo(id) {
  return request(`combos/${encodeURIComponent(id)}`, { method: "DELETE" });
}
