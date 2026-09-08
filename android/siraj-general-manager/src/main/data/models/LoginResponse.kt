package com.siraj.erp.generalmanager.data.models

import com.google.gson.annotations.SerializedName

data class LoginResponse(
    val status: String,
    val message: String,
    val data: UserData?
)

data class UserData(
    val user: UserInfo,
    @SerializedName("allowed_apps")
    val allowedApps: List<String>,
    val permissions: List<String>,
    val token: String
)

data class UserInfo(
    val id: String,
    val full_name: String,
    val username: String,
    val email: String?,
    val role: String?,
    val status: String
)
