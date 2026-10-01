const API_URL = "http://127.0.0.1:8000/api/ho-so";

async function request(path = "", options = {}) {
    const response = await fetch(`${API_URL}${path}`, {
        ...options,
        headers: {
            Accept: "application/json",
            "Content-Type": "application/json",
            Authorization: `Bearer ${localStorage.getItem("token")}`,
        },
    }).catch((error) => {
        if (error.name === "AbortError") throw error;
        throw new Error("Không thể kết nối đến máy chủ. Vui lòng thử lại.");
    });
    const result = await response.json().catch(() => ({}));
    if (!response.ok) {
        const messages = { 401: "Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.", 403: "Bạn không có quyền thực hiện thao tác này.", 422: "Vui lòng kiểm tra các thông tin bên dưới.", 429: "Bạn thao tác quá nhanh. Vui lòng thử lại sau ít phút." };
        const error = new Error(messages[response.status] || "Không thể xử lý yêu cầu. Vui lòng thử lại.");
        error.status = response.status;
        error.fields = result.errors || {};
        throw error;
    }
    return result;
}

export const loadMember = (signal) => request("", { signal });
export const saveMember = (data) => request("", { method: "PATCH", body: JSON.stringify(data) });
export const changeMemberPassword = (data) => request("/mat-khau", { method: "PATCH", body: JSON.stringify(data) });
