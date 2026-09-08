package com.siraj.erp.generalmanager.utils

import android.content.Context
import androidx.datastore.preferences.core.*
import androidx.datastore.preferences.preferencesDataStore
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map

// إنشاء DataStore
private val Context.dataStore by preferencesDataStore(name = "siraj_auth")

class SessionManager(private val context: Context) {

    companion object {
        private val TOKEN_KEY = stringPreferencesKey("access_token")
        private val USER_ID_KEY = stringPreferencesKey("user_id")
        private val USERNAME_KEY = stringPreferencesKey("username")
        private val FULL_NAME_KEY = stringPreferencesKey("full_name")
        private val PERMISSIONS_KEY = stringPreferencesKey("permissions")
        private val APPS_KEY = stringPreferencesKey("allowed_apps")
    }

    // حفظ بيانات المستخدم بعد تسجيل الدخول
    suspend fun saveSession(token: String, userId: String, username: String, fullName: String, permissions: List<String>, apps: List<String>) {
        context.dataStore.edit { preferences ->
            preferences[TOKEN_KEY] = token
            preferences[USER_ID_KEY] = userId
            preferences[USERNAME_KEY] = username
            preferences[FULL_NAME_KEY] = fullName
            preferences[PERMISSIONS_KEY] = permissions.joinToString(",")
            preferences[APPS_KEY] = apps.joinToString(",")
        }
    }

    // قراءة التوكن (لإضافته في رؤوس الطلبات لاحقاً)
    val tokenFlow: Flow<String?> = context.dataStore.data.map { preferences ->
        preferences[TOKEN_KEY]
    }

    // قراءة اسم المستخدم (للعرض في واجهة التطبيق)
    val usernameFlow: Flow<String?> = context.dataStore.data.map { preferences ->
        preferences[USERNAME_KEY]
    }

    // التحقق من حالة تسجيل الدخول
    val isLoggedIn: Flow<Boolean> = context.dataStore.data.map { preferences ->
        preferences[TOKEN_KEY] != null
    }

    // مسح الجلسة (تسجيل الخروج)
    suspend fun clearSession() {
        context.dataStore.edit { it.clear() }
    }
}
