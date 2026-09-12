import {
  BrowserRouter,
  Routes,
  Route,
  Navigate,
} from "react-router-dom";

import CustomerLayout from "./layouts/CustomerLayout";

import LoginCustomer from "./pages/LoginCustomer";
import HomeCustomer from "./pages/HomeCustomer";
import MovieShowtimes from "./pages/MovieShowtimes";
import SeatSelection from "./pages/SeatSelection";
import Payment from "./pages/Payment";
import ComboSelection from "./pages/ComboSelection";

import LoginInternal from "./pages/LoginInternal";
import Dashboard from "./pages/Dashboard";
import CustomerManagement from "./pages/CustomerManagement";
import PromotionManagement from "./pages/PromotionManagement";
import OrderManagement from "./pages/OrderManagement";
import CounterSale from "./pages/CounterSale";

// =========================
// QUẢN LÝ NHÂN VIÊN
// =========================
import NhanVienManagement from "./pages/NhanVienManagement";


// =========================
// BẢO VỆ DASHBOARD
// NHÂN VIÊN + QUẢN LÝ
// =========================
function InternalRoute({ children }) {
  const token = localStorage.getItem("token");
  const vaiTro = localStorage.getItem("vaiTro");

  const internalRoles = [
    "NHAN_VIEN",
    "QUAN_LY",
  ];

  if (
    !token ||
    !internalRoles.includes(vaiTro)
  ) {
    return (
      <Navigate
        to="/internal/login"
        replace
      />
    );
  }

  return children;
}


// =========================
// CHỈ DÀNH CHO QUẢN LÝ
// =========================
function ManagerRoute({ children }) {
  const token = localStorage.getItem("token");
  const vaiTro = localStorage.getItem("vaiTro");

  // Chưa đăng nhập
  if (!token) {
    return (
      <Navigate
        to="/internal/login"
        replace
      />
    );
  }

  // Có đăng nhập nhưng không phải quản lý
  if (vaiTro !== "QUAN_LY") {
    return (
      <Navigate
        to="/dashboard"
        replace
      />
    );
  }

  return children;
}


// =========================
// BẢO VỆ KHÁCH HÀNG
// =========================
function CustomerRoute({ children }) {
  const token = localStorage.getItem("token");
  const vaiTro = localStorage.getItem("vaiTro");

  if (
    !token ||
    vaiTro !== "KHACH_HANG"
  ) {
    return (
      <Navigate
        to="/login"
        replace
        state={{
          from: window.location.pathname,
        }}
      />
    );
  }

  return children;
}


function App() {
  return (
    <BrowserRouter>
      <Routes>

        {/* =========================
            TRANG CHỦ CÔNG KHAI
        ========================= */}

        <Route
          path="/"
          element={
            <Navigate
              to="/home"
              replace
            />
          }
        />

        <Route
          path="/home"
          element={
            <CustomerLayout>
              <HomeCustomer />
            </CustomerLayout>
          }
        />


        {/* =========================
            ĐẶT VÉ
        ========================= */}

        <Route
          path="/dat-ve/:maPhim"
          element={
            <CustomerRoute>
              <CustomerLayout>
                <MovieShowtimes />
              </CustomerLayout>
            </CustomerRoute>
          }
        />

        <Route
          path="/chon-ghe/:maLichChieu"
          element={
            <CustomerRoute>
              <CustomerLayout>
                <SeatSelection />
              </CustomerLayout>
            </CustomerRoute>
          }
        />

        <Route
          path="/thanh-toan/:maDonHang"
          element={
            <CustomerRoute>
              <CustomerLayout>
                <Payment />
              </CustomerLayout>
            </CustomerRoute>
          }
        />

        <Route
          path="/chon-combo/:maDonHang"
          element={
            <CustomerRoute>
              <CustomerLayout>
                <ComboSelection />
              </CustomerLayout>
            </CustomerRoute>
          }
        />


        {/* =========================
            ĐĂNG NHẬP / ĐĂNG KÝ
        ========================= */}

        <Route
          path="/login"
          element={
            <LoginCustomer
              initialTab="login"
            />
          }
        />

        <Route
          path="/register"
          element={
            <LoginCustomer
              initialTab="register"
            />
          }
        />


        {/* =========================
            NHÂN VIÊN / QUẢN LÝ
        ========================= */}

        <Route
          path="/internal/login"
          element={
            <LoginInternal />
          }
        />


        {/* DASHBOARD CHUNG */}
        <Route
          path="/dashboard"
          element={
            <InternalRoute>
              <Dashboard />
            </InternalRoute>
          }
        />

        <Route
          path="/dashboard/khach-hang"
          element={
            <InternalRoute>
              {localStorage.getItem("vaiTro") === "QUAN_LY" ? (
                <Dashboard>
                  <CustomerManagement />
                </Dashboard>
              ) : (
                <Navigate to="/dashboard" replace />
              )}
            </InternalRoute>
          }
        />

        <Route
          path="/dashboard/khuyen-mai"
          element={
            <InternalRoute>
              {localStorage.getItem("vaiTro") === "QUAN_LY" ? (
                <Dashboard>
                  <PromotionManagement />
                </Dashboard>
              ) : (
                <Navigate to="/dashboard" replace />
              )}
            </InternalRoute>
          }
        />

        <Route
          path="/dashboard/don-hang"
          element={
            <InternalRoute>
              <Dashboard>
                <OrderManagement />
              </Dashboard>
            </InternalRoute>
          }
        />

        <Route
          path="/dashboard/ban-ve-tai-quay"
          element={
            <InternalRoute>
              <Dashboard>
                <CounterSale />
              </Dashboard>
            </InternalRoute>
          }
        />

        {/* =========================
            QUẢN LÝ NHÂN VIÊN
            CHỈ QUAN_LY
        ========================= */}

        <Route
          path="/dashboard/nhan-vien"
          element={
            <ManagerRoute>
              <Dashboard>
                <NhanVienManagement />
              </Dashboard>
            </ManagerRoute>
          }
        />

      </Routes>
    </BrowserRouter>
  );
}

export default App;
