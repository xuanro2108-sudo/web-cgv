import { useState } from "react";
import {
  useLocation,
  useNavigate,
} from "react-router-dom";
import "./Dashboard.css";

function Dashboard({ children }) {
  const navigate = useNavigate();
  const location = useLocation();

  // =========================
  // KIỂM TRA TRANG ĐANG MỞ
  // =========================
  const customerPage =
    location.pathname ===
    "/dashboard/khach-hang";

  const promotionPage =
    location.pathname ===
    "/dashboard/khuyen-mai";

  const employeePage =
    location.pathname ===
    "/dashboard/nhan-vien";

  // =========================
  // THÔNG TIN TÀI KHOẢN
  // =========================
  const taiKhoan = JSON.parse(
    localStorage.getItem("taiKhoan") ||
      "null"
  );

  const vaiTro =
    localStorage.getItem("vaiTro");

  const [
    logoutLoading,
    setLogoutLoading,
  ] = useState(false);

  // =========================
  // TÊN HIỂN THỊ VAI TRÒ
  // =========================
  const getRoleName = () => {
    if (vaiTro === "QUAN_LY") {
      return "Quản lý";
    }

    if (vaiTro === "NHAN_VIEN") {
      return "Nhân viên";
    }

    return "Không xác định";
  };

  // =========================
  // DANH SÁCH CHỨC NĂNG
  // =========================
  const functions = [
    {
      title: "Quản lý phim",
      description:
        "Quản lý danh sách phim, thông tin và trạng thái phim.",
      roles: ["QUAN_LY"],
      path: "/dashboard/phim",
    },

    {
      title: "Quản lý lịch chiếu",
      description:
        "Tạo, cập nhật và theo dõi lịch chiếu phim.",
      roles: [
        "QUAN_LY",
        "NHAN_VIEN",
      ],
      path: "/dashboard/lich-chieu",
    },

    {
      title: "Quản lý phòng chiếu",
      description:
        "Quản lý phòng chiếu, sơ đồ ghế và tình trạng ghế.",
      roles: ["QUAN_LY"],
      path: "/dashboard/phong-chieu",
    },

    {
      title: "Đơn hàng & vé",
      description:
        "Theo dõi đơn hàng, vé đã đặt và tình trạng thanh toán.",
      roles: [
        "QUAN_LY",
        "NHAN_VIEN",
      ],
      path: "/dashboard/don-hang",
    },

    {
      title: "Tài khoản",
      description:
        "Tra cứu và quản lý tài khoản khách hàng.",
      roles: ["QUAN_LY"],
      path: "/dashboard/khach-hang",
    },

    {
      title: "Combo & sản phẩm",
      description:
        "Quản lý combo bắp nước và các sản phẩm bán kèm.",
      roles: [
        "QUAN_LY",
        "NHAN_VIEN",
      ],
      path: "/dashboard/combo",
    },

    {
      title: "Khuyến mãi",
      description:
        "Quản lý chương trình ưu đãi và khuyến mãi.",
      roles: ["QUAN_LY"],
      path: "/dashboard/khuyen-mai",
    },

    {
      title: "Nhân viên",
      description:
        "Quản lý thông tin nhân viên và tài khoản nội bộ.",
      roles: ["QUAN_LY"],
      path: "/dashboard/nhan-vien",
    },

    {
      title: "Thống kê",
      description:
        "Theo dõi doanh thu, số vé bán và tình hình hoạt động.",
      roles: ["QUAN_LY"],
      path: "/dashboard/thong-ke",
    },
  ];

  // =========================
  // LỌC CHỨC NĂNG THEO ROLE
  // =========================
  const allowedFunctions =
    functions.filter((item) =>
      item.roles.includes(vaiTro)
    );

  // =========================
  // MỞ CHỨC NĂNG
  // =========================
  const handleOpenFunction = (
    path
  ) => {
    const availablePaths = [
      "/dashboard/khach-hang",
      "/dashboard/khuyen-mai",
      "/dashboard/nhan-vien",
    ];

    if (
      availablePaths.includes(path)
    ) {
      navigate(path);
      return;
    }

    alert(
      "Chức năng này sẽ được xây dựng ở bước tiếp theo."
    );

    console.log(
      "Đường dẫn:",
      path
    );
  };

  // =========================
  // XÓA DỮ LIỆU ĐĂNG NHẬP
  // =========================
  const clearLoginData = () => {
    localStorage.removeItem(
      "token"
    );

    localStorage.removeItem(
      "vaiTro"
    );

    localStorage.removeItem(
      "taiKhoan"
    );

    localStorage.removeItem(
      "khachHang"
    );
  };

  // =========================
  // ĐĂNG XUẤT
  // =========================
  const handleLogout = async () => {
    const token =
      localStorage.getItem("token");

    if (!token) {
      clearLoginData();

      navigate(
        "/internal/login"
      );

      return;
    }

    setLogoutLoading(true);

    try {
      await fetch(
        "http://127.0.0.1:8000/api/auth/logout",
        {
          method: "POST",

          headers: {
            Accept:
              "application/json",

            Authorization:
              `Bearer ${token}`,
          },
        }
      );
    } catch (error) {
      console.error(
        "Lỗi đăng xuất:",
        error
      );
    } finally {
      clearLoginData();

      setLogoutLoading(false);

      navigate(
        "/internal/login"
      );
    }
  };

  return (
    <div className="dashboard-layout">

      {/* =========================
          SIDEBAR
      ========================= */}
      <aside className="dashboard-sidebar">

        {/* =========================
            LOGO
        ========================= */}
        <div className="dashboard-logo">

          <div className="dashboard-cgv">
            CGV
          </div>

          <div className="dashboard-branch">
            AEON MALL HÀ ĐÔNG
          </div>

        </div>


        {/* =========================
            THÔNG TIN TÀI KHOẢN
        ========================= */}
        <div className="dashboard-user">

          <strong>
            {taiKhoan?.tenDangNhap ||
              "Tài khoản"}
          </strong>

          <span>
            {getRoleName()}
          </span>

          <small>
            Đang hoạt động
          </small>

        </div>


        {/* =========================
            MENU
        ========================= */}
        <nav className="dashboard-menu">

          {/* TRANG CHÍNH */}
          <button
            type="button"
            className={
              `dashboard-menu-item ${
                !customerPage &&
                !promotionPage &&
                !employeePage
                  ? "active"
                  : ""
              }`
            }
            onClick={() =>
              navigate(
                "/dashboard"
              )
            }
          >
            Trang chính
          </button>


          {/* =========================
              NHÂN VIÊN + QUẢN LÝ
          ========================= */}
          {(vaiTro === "QUAN_LY" ||
            vaiTro ===
              "NHAN_VIEN") && (
            <>

              {/* LỊCH CHIẾU */}
              <button
                type="button"
                className="dashboard-menu-item"
              >
                Lịch chiếu
              </button>


              {/* ĐƠN HÀNG */}
              <button
                type="button"
                className="dashboard-menu-item"
              >
                Đơn hàng & vé
              </button>


              {/* TÀI KHOẢN KHÁCH HÀNG */}
              {vaiTro ===
                "QUAN_LY" && (
                <button
                  type="button"
                  className={
                    `dashboard-menu-item ${
                      customerPage
                        ? "active"
                        : ""
                    }`
                  }
                  onClick={() =>
                    navigate(
                      "/dashboard/khach-hang"
                    )
                  }
                  aria-current={
                    customerPage
                      ? "page"
                      : undefined
                  }
                >
                  Tài khoản
                </button>
              )}


              {/* COMBO */}
              <button
                type="button"
                className="dashboard-menu-item"
              >
                Combo
              </button>

            </>
          )}


          {/* =========================
              CHỈ QUẢN LÝ
          ========================= */}
          {vaiTro ===
            "QUAN_LY" && (
            <>

              {/* PHIM */}
              <button
                type="button"
                className="dashboard-menu-item"
              >
                Phim
              </button>


              {/* PHÒNG CHIẾU */}
              <button
                type="button"
                className="dashboard-menu-item"
              >
                Phòng chiếu
              </button>


              {/* KHUYẾN MÃI */}
              <button
                type="button"
                className={
                  `dashboard-menu-item ${
                    promotionPage
                      ? "active"
                      : ""
                  }`
                }
                onClick={() =>
                  navigate(
                    "/dashboard/khuyen-mai"
                  )
                }
                aria-current={
                  promotionPage
                    ? "page"
                    : undefined
                }
              >
                Khuyến mãi
              </button>


              {/* =========================
                  NHÂN VIÊN
              ========================= */}
              <button
                type="button"
                className={
                  `dashboard-menu-item ${
                    employeePage
                      ? "active"
                      : ""
                  }`
                }
                onClick={() =>
                  navigate(
                    "/dashboard/nhan-vien"
                  )
                }
                aria-current={
                  employeePage
                    ? "page"
                    : undefined
                }
              >
                Nhân viên
              </button>


              {/* THỐNG KÊ */}
              <button
                type="button"
                className="dashboard-menu-item"
              >
                Thống kê
              </button>

            </>
          )}

        </nav>


        {/* =========================
            ĐĂNG XUẤT
        ========================= */}
        <div className="dashboard-logout">

          <button
            type="button"
            onClick={
              handleLogout
            }
            disabled={
              logoutLoading
            }
          >
            {logoutLoading
              ? "Đang đăng xuất..."
              : "Đăng xuất"}
          </button>

        </div>

      </aside>


      {/* =========================
          NỘI DUNG CHÍNH
      ========================= */}
      <main className="dashboard-main">

        {children ? (
          children
        ) : (
          <section className="dashboard-panel">

            {/* =========================
                TIÊU ĐỀ
            ========================= */}
            <div className="dashboard-heading">

              <h1>
                Chức năng của tài khoản này
              </h1>

              <p>
                Bạn bấm vào chức năng để truy cập
                module phù hợp. Các chức năng hiển
                thị phụ thuộc vào quyền của tài
                khoản đang đăng nhập.
              </p>

            </div>


            {/* =========================
                GRID CHỨC NĂNG
            ========================= */}
            <div className="function-grid">

              {allowedFunctions.map(
                (item) => (

                  <div
                    className="function-card"
                    key={
                      item.title
                    }
                  >

                    <h2>
                      {item.title}
                    </h2>

                    <p>
                      {
                        item.description
                      }
                    </p>

                    <button
                      type="button"
                      onClick={() =>
                        handleOpenFunction(
                          item.path
                        )
                      }
                    >
                      Mở chức năng
                    </button>

                  </div>
                )
              )}

            </div>

          </section>
        )}

      </main>

    </div>
  );
}

export default Dashboard;