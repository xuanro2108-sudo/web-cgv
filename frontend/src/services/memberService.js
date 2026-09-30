const API_URL = "http://127.0.0.1:8000/api/ho-so";

async function request(path = "", options = {}) {
    const response = await fetch(`${API_URL}${path}`, {
        ...options,
        headers: {
            Accept: "application/json",
            "Content-Type": "application/json",
            Authorization: `Bearer ${localStorage.getItem("token")}`,
        },
    });
    const result = await response.json().catch(() => ({}));
    if (!response.ok) {
        const error = new Error(result.message || "Không thể xử lý yêu cầu. Vui lòng thử lại.");
        error.status = response.status;
        error.fields = result.errors || {};
        throw error;
    }
    return result;
}

export const loadMember = (signal) => request("", { signal });
export const saveMember = (data) => request("", { method: "PATCH", body: JSON.stringify(data) });
export const changeMemberPassword = (data) => request("/mat-khau", { method: "PATCH", body: JSON.stringify(data) });
