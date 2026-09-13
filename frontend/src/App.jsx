import {
  BrowserRouter,
  Routes,
  Route,
  Navigate,
} from "react-router-dom";

import CustomerLayout from "./layouts/CustomerLayout";

import LichChieuManagement from "./pages/LichChieuManagement";
import PhongChieuManagement from "./pages/PhongChieuManagement";

import LoginCustomer from "./pages/LoginCustomer";
import HomeCustomer from "./pages/HomeCustomer";
import MovieShowtimes from "./pages/MovieShowtimes";
import SeatSelection from "./pages/SeatSelection";
import Payment from "./pages/Payment";
import ComboSelection from "./pages/ComboSelection";
import ThongKe from "./pages/ThongKe";
import LoginInternal from "./pages/LoginInternal";
import Dashboard from "./pages/Dashboard";
import MovieManagement from "./pages/MovieManagement";
import ComboManagement from "./pages/ComboManagement";
import ProductManagement from "./pages/ProductManagement";
import CustomerManagement from "./pages/CustomerManagement";
import PromotionManagement from "./pages/PromotionManagement";
import OrderManagement from "./pages/OrderManagement";
import CounterSale from "./pages/CounterSale";
import NhanVienManagement from "./pages/NhanVienManagement";


// =========================
// BẢO VỆ DASHBOARD
// NHÂN VIÊN + QUẢN LÝ
// =========================
function InternalRoute({
  children,
  managerOnly = false,
}) {
  const token =
    localStorage.getItem("token");

  const vaiTro =
    localStorage.getItem("vaiTro");

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

  if (
    managerOnly &&
    vaiTro !== "QUAN_LY"
  ) {
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
// CHỈ DÀNH CHO QUẢN LÝ
// =========================
function ManagerRoute({
  children,
}) {
  const token =
    localStorage.getItem("token");

  const vaiTro =
    localStorage.getItem("vaiTro");

  if (!token) {
    return (
      <Navigate
        to="/internal/login"
        replace
      />
    );
  }

  if (
    vaiTro !== "QUAN_LY"
  ) {
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
function CustomerRoute({
  children,
}) {
  const token =
    localStorage.getItem("token");

  const vaiTro =
    localStorage.getItem("vaiTro");

  if (
    !token ||
    vaiTro !== "KHACH_HANG"
  ) {
    return (
      <Navigate
        to="/login"
        replace
        state={{
          from:
            window.location.pathname,
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
            ĐĂNG NHẬP NỘI BỘ
        ========================= */}

        <Route
          path="/internal/login"
          element={
            <LoginInternal />
          }
        />


        {/* =========================
            DASHBOARD CHUNG
        ========================= */}

        <Route
          path="/dashboard"
          element={
            <InternalRoute>
              <Dashboard />
            </InternalRoute>
          }
        />


        {/* =========================
            TÀI KHOẢN KHÁCH HÀNG
        ========================= */}

        <Route
          path="/dashboard/khach-hang"
          element={
            <InternalRoute managerOnly>
              <Dashboard>
                <CustomerManagement />
              </Dashboard>
            </InternalRoute>
          }
        />


        {/* =========================
            KHUYẾN MÃI
        ========================= */}

        <Route
          path="/dashboard/khuyen-mai"
          element={
            <InternalRoute managerOnly>
              <Dashboard>
                <PromotionManagement />
              </Dashboard>
            </InternalRoute>
          }
        />


        {/* =========================
            ĐƠN HÀNG & VÉ
        ========================= */}

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


        {/* =========================
            BÁN VÉ TẠI QUẦY
        ========================= */}

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
            CHỈ QUẢN LÝ
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


        {/* =========================
            THỐNG KÊ
            CHỈ QUẢN LÝ
        ========================= */}

        <Route
          path="/dashboard/thong-ke"
          element={
            <ManagerRoute>
              <Dashboard>
                <ThongKe />
              </Dashboard>
            </ManagerRoute>
          }
        />


        {/* =========================
            QUẢN LÝ LỊCH CHIẾU
            CHỈ QUẢN LÝ
        ========================= */}

        <Route
          path="/dashboard/lich-chieu"
          element={
            <ManagerRoute>
              <Dashboard>
                <LichChieuManagement />
              </Dashboard>
            </ManagerRoute>
          }
        />


        {/* =========================
            QUẢN LÝ PHÒNG CHIẾU
            CHỈ QUẢN LÝ
        ========================= */}

        <Route
          path="/dashboard/phong-chieu"
          element={
            <ManagerRoute>
              <Dashboard>
                <PhongChieuManagement />
              </Dashboard>
            </ManagerRoute>
          }
        />


        {/* =========================
            QUẢN LÝ PHIM
        ========================= */}

        <Route
          path="/dashboard/phim"
          element={
            <InternalRoute managerOnly>
              <Dashboard>
                <MovieManagement />
              </Dashboard>
            </InternalRoute>
          }
        />


        {/* =========================
            QUẢN LÝ COMBO
        ========================= */}

        <Route
          path="/dashboard/combo"
          element={
            <InternalRoute>
              <Dashboard>
                <ComboManagement />
              </Dashboard>
            </InternalRoute>
          }
        />


        {/* =========================
            QUẢN LÝ SẢN PHẨM
        ========================= */}

        <Route
          path="/dashboard/san-pham"
          element={
            <InternalRoute managerOnly>
              <Dashboard>
                <ProductManagement />
              </Dashboard>
            </InternalRoute>
          }
        />

      </Routes>

    </BrowserRouter>
  );
}

export default App;