const API = "http://127.0.0.1:8000/api/phims";

async function request(path = "", options = {}) {
  const response = await fetch(`${API}${path}`, {
    ...options,
    headers: {
      Accept: "application/json",
      ...(!(options.body instanceof FormData) ? { "Content-Type": "application/json" } : {}),
      Authorization: `Bearer ${localStorage.getItem("token")}`,
    },
  });
  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    const validation = Object.values(data.errors || {}).flat().join(" ");
    throw new Error(validation || (response.status >= 500
      ? "Không thể thực hiện thao tác. Phim có thể đang có dữ liệu liên quan. Vui lòng thử lại hoặc chuyển trạng thái phim."
      : data.message) || "Không thể kết nối dữ liệu phim.");
  }
  return data;
}

export async function loadMovies(signal) {
  const first = await request("?page=1", { signal });
  const movies = [...first.data];
  for (let page = 2; page <= first.last_page; page += 1) {
    const result = await request(`?page=${page}`, { signal });
    movies.push(...result.data);
  }
  return movies;
}

export function saveMovie(movie, editing, posterFile) {
  if (posterFile) {
    const body = new FormData();
    for (const [key, value] of Object.entries(movie)) {
      if (key !== "hinhAnh") body.append(key, value ?? "");
    }
    body.append("anh", posterFile);
    if (editing) body.append("_method", "PUT");
    return request(editing ? `/${encodeURIComponent(movie.maPhim)}` : "", { method: "POST", body });
  }
  return request(editing ? `/${encodeURIComponent(movie.maPhim)}` : "", {
    method: editing ? "PUT" : "POST",
    body: JSON.stringify(movie),
  });
}

export function deleteMovie(id) {
  return request(`/${encodeURIComponent(id)}`, { method: "DELETE" });
}
