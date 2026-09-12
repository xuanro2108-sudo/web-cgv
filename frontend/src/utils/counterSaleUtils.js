export const api = (import.meta.env.VITE_API_URL || "http://127.0.0.1:8000/api").replace(/\/$/, "");

export const money = (value) =>
  new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
  }).format(value || 0);

export const showtimeDate = (value) =>
  value
    ? new Intl.DateTimeFormat("vi-VN", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
      }).format(new Date(value))
    : "—";

export const showtimeTime = (value) => (value ? String(value).slice(0, 5) : "—");

export async function request(path, options = {}) {
  const response = await fetch(api + path, {
    ...options,
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      Authorization: `Bearer ${localStorage.getItem("token") || ""}`,
      ...(options.headers || {}),
    },
  });

  const body = await response.json().catch(() => null);

  if (!response.ok) {
    throw new Error(body?.message || "Không thể thực hiện yêu cầu.");
  }

  return body;
}

export const collection = (body) =>
  Array.isArray(body) ? body : body?.data || [];

export function normalizeShowtimeDate(value) {
  if (typeof value !== "string") return "";
  const match = value.match(/^(\d{4}-\d{2}-\d{2})/);
  return match ? match[1] : "";
}

export function formatShowtimeDate(dateString) {
  if (!dateString) return { day: "", month: "", weekday: "" };

  const [year, month, day] = dateString.split("-").map(Number);
  const dateObject = new Date(year, month - 1, day);
  const weekdays = ["CN", "T2", "T3", "T4", "T5", "T6", "T7"];

  return {
    day: String(day).padStart(2, "0"),
    month: String(month).padStart(2, "0"),
    year: String(year),
    weekday: weekdays[dateObject.getDay()] || "",
  };
}

export const getSeatLabel = (seat) =>
  `${seat.hang || "?"}${seat.cot ?? "?"}`;

export const getSeatPrice = (seat, seatPrices) =>
  seatPrices?.[seat?.loaiGhe] || 0;
