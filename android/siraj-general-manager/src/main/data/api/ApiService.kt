package com.siraj.erp.generalmanager.data.api

import com.siraj.erp.generalmanager.data.models.LoginRequest
import com.siraj.erp.generalmanager.data.models.LoginResponse
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.POST

interface ApiService {
    @POST("api/auth/login")
    suspend fun login(@Body request: LoginRequest): Response<LoginResponse>
}
