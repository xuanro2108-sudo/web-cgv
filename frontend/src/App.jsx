import {
  BrowserRouter,
  Routes,
  Route,
  Navigate,
} from "react-router-dom";

import LoginCustomer from "./pages/LoginCustomer";
import HomeCustomer from "./pages/HomeCustomer";

import LoginInternal from "./pages/LoginInternal";
import Dashboard from "./pages/Dashboard";
import CustomerManagement from "./pages/CustomerManagement";
import PromotionManagement from "./pages/PromotionManagement";

// =========================
// BẢO VỆ TRANG KHÁCH HÀNG
// =========================
function CustomerRoute({ children }) {
  const token = localStorage.getItem("token");
  const vaiTro = localStorage.getItem("vaiTro");

  if (!token || vaiTro !== "KHACH_HANG") {
    return <Navigate to="/" replace />;
  }

  return children;
}

// =========================
// BẢO VỆ DASHBOARD
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

function App() {
  return (
    <BrowserRouter>

      <Routes>

        {/* =========================
            KHÁCH HÀNG
        ========================= */}

<Route
  path="/"
  element={<LoginCustomer />}
/>
        <Route
          path="/home"
          element={
            <CustomerRoute>
              <HomeCustomer />
            </CustomerRoute>
          }
        />


        {/* =========================
            NHÂN VIÊN / QUẢN LÝ
        ========================= */}

        <Route
          path="/internal/login"
          element={<LoginInternal />}
        />

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
                <Dashboard><CustomerManagement /></Dashboard>
              ) : <Navigate to="/dashboard" replace />}
            </InternalRoute>
          }
        />
        <Route path="/dashboard/khuyen-mai" element={
          <InternalRoute>
            {localStorage.getItem("vaiTro") === "QUAN_LY" ? <Dashboard><PromotionManagement /></Dashboard> : <Navigate to="/dashboard" replace />}
          </InternalRoute>
        } />
      </Routes>

    </BrowserRouter>
  );
}

export default App;
