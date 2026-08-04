import { Navigate } from "react-router-dom";


function ProtectedRoute({ children, allowedRoles }) {


    const token = localStorage.getItem("token");

    const user = JSON.parse(
        localStorage.getItem("user")
    );


    // No login
    if(!token || !user){

        return <Navigate to="/login" replace />;

    }


    // Role not allowed
    if(!allowedRoles.includes(user.role)){

        return <Navigate to="/login" replace />;

    }


    return children;

}


export default ProtectedRoute;