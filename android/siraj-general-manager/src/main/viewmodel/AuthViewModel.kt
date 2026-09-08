package com.siraj.erp.generalmanager.viewmodel

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.siraj.erp.generalmanager.data.api.ApiClient
import com.siraj.erp.generalmanager.data.models.LoginRequest
import com.siraj.erp.generalmanager.utils.SessionManager
import kotlinx.coroutines.launch

class AuthViewModel(private val sessionManager: SessionManager) : ViewModel() {

    private val _loginResult = MutableLiveData<LoginUiState>()
    val loginResult: LiveData<LoginUiState> = _loginResult

    fun login(username: String, password: String) {
        // عرض حالة التحميل
        _loginResult.value = LoginUiState.Loading

        viewModelScope.launch {
            try {
                val response = ApiClient.apiService.login(LoginRequest(username, password))

                if (response.isSuccessful && response.body()?.status == "success") {
                    val data = response.body()?.data
                    if (data != null) {
                        // ✅ حفظ البيانات في الجلسة
                        sessionManager.saveSession(
                            token = data.token,
                            userId = data.user.id,
                            username = data.user.username,
                            fullName = data.user.full_name,
                            permissions = data.permissions,
                            apps = data.allowedApps
                        )
                        _loginResult.value = LoginUiState.Success(data)
                    } else {
                        _loginResult.value = LoginUiState.Error("❌ البيانات غير مكتملة")
                    }
                } else {
                    // قراءة رسالة الخطأ من الخادم
                    val errorMsg = response.body()?.message ?: "فشل تسجيل الدخول"
                    _loginResult.value = LoginUiState.Error(errorMsg)
                }
            } catch (e: Exception) {
                _loginResult.value = LoginUiState.Error("⚠️ خطأ في الاتصال: ${e.localizedMessage}")
            }
        }
    }
}

// حالات واجهة تسجيل الدخول
sealed class LoginUiState {
    object Loading : LoginUiState()
    data class Success(val data: UserData) : LoginUiState()
    data class Error(val message: String) : LoginUiState()
}
