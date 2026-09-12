import { useState } from "react";
import "./ThongKe.css";

const API =
  "http://127.0.0.1:8000/api/quan-ly/thong-ke/doanh-thu-ve";

function ThongKe() {
  const [tuNgay, setTuNgay] = useState("");
  const [denNgay, setDenNgay] = useState("");
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const formatMoney = (value) =>
    Number(value || 0).toLocaleString("vi-VN") + " đ";

  const formatDate = (date) => {
    if (!date) return "";
    return new Date(date + "T00:00:00").toLocaleDateString(
      "vi-VN"
    );
  };

  const handleThongKe = async () => {
    setError("");
    setData(null);

    if (!tuNgay || !denNgay) {
      setError("Vui lòng chọn đầy đủ khoảng thời gian.");
      return;
    }

    if (tuNgay > denNgay) {
      setError("Đến ngày phải lớn hơn hoặc bằng từ ngày.");
      return;
    }

    const token = localStorage.getItem("token");

    setLoading(true);

    try {
      const params = new URLSearchParams({
        tuNgay,
        denNgay,
      });

      const response = await fetch(
        `${API}?${params.toString()}`,
        {
          headers: {
            Accept: "application/json",
            Authorization: `Bearer ${token}`,
          },
        }
      );

      const result = await response.json();

      if (!response.ok) {
        throw new Error(
          result.message || "Không thể tải dữ liệu thống kê."
        );
      }

      setData(result);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="statistics-page">
      <div className="statistics-header">
        <div>
          <h1>Báo cáo thống kê</h1>
          <p>Thống kê doanh thu bán vé theo khoảng thời gian.</p>
        </div>
      </div>

      <div className="statistics-filter">
        <div className="filter-group">
          <label>Từ ngày</label>
          <input
            type="date"
            value={tuNgay}
            onChange={(e) => setTuNgay(e.target.value)}
          />
        </div>

        <div className="filter-group">
          <label>Đến ngày</label>
          <input
            type="date"
            value={denNgay}
            min={tuNgay}
            onChange={(e) => setDenNgay(e.target.value)}
          />
        </div>

        <button
          type="button"
          className="statistics-button"
          onClick={handleThongKe}
          disabled={loading}
        >
          {loading ? "Đang thống kê..." : "Xem thống kê"}
        </button>
      </div>

      {error && (
        <div className="statistics-error">
          {error}
        </div>
      )}

      {data && (
        <>
          <div className="statistics-summary">
            <div className="summary-card">
              <span>Tổng doanh thu bán vé</span>
              <strong>
                {formatMoney(data.tongDoanhThu)}
              </strong>
            </div>

            <div className="summary-card">
              <span>Tổng số vé bán</span>
              <strong>{data.tongSoVe} vé</strong>
            </div>

            <div className="summary-card">
              <span>Khoảng thời gian</span>
              <strong>
                {formatDate(tuNgay)} - {formatDate(denNgay)}
              </strong>
            </div>
          </div>

          {!data.coDuLieu ? (
            <div className="statistics-empty">
              Không có dữ liệu trong khoảng thời gian này.
            </div>
          ) : (
            <div className="statistics-table-box">
              <h2>Chi tiết doanh thu theo ngày</h2>

              <div className="statistics-table-wrapper">
                <table className="statistics-table">
                  <thead>
                    <tr>
                      <th>Ngày</th>
                      <th>Số vé bán</th>
                      <th>Doanh thu</th>
                    </tr>
                  </thead>

                  <tbody>
                    {data.theoNgay.map((item) => (
                      <tr key={item.ngay}>
                        <td>
                          {formatDate(item.ngay)}
                        </td>
                        <td>{item.soVe}</td>
                        <td>
                          {formatMoney(item.doanhThu)}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}
        </>
      )}
    </div>
  );
}

export default ThongKe;