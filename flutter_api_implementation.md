# GoalMoney - Flutter API Implementation Guide

Dokumentasi untuk mengimplementasikan backend API di Flutter.

---

## 1. Setup API Client

### `lib/core/constants.dart`
```dart
class AppConstants {
  // Ganti dengan URL backend Anda
  static const String baseUrl = 'https://your-api-domain.com/api';
  
  // Untuk emulator Android ke localhost
  // static const String baseUrl = 'http://10.0.2.2:8000/api';
}
```

### `lib/core/api_client.dart`
```dart
import 'package:dio/dio.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'constants.dart';

class ApiClient {
  static final Dio _dio = Dio(
    BaseOptions(
      baseUrl: AppConstants.baseUrl,
      connectTimeout: const Duration(seconds: 15),
      receiveTimeout: const Duration(seconds: 15),
      headers: {'Content-Type': 'application/json'},
    ),
  );

  static Dio get instance {
    _dio.interceptors.clear();
    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final prefs = await SharedPreferences.getInstance();
          final token = prefs.getString('token');
          if (token != null) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          return handler.next(options);
        },
        onError: (DioException e, handler) {
          // Handle 401 - auto logout
          if (e.response?.statusCode == 401) {
            // Trigger logout
          }
          return handler.next(e);
        },
      ),
    );
    return _dio;
  }
}
```

---

## 2. Authentication Endpoints

### Register
```dart
// POST /auth/register
Future<void> register(String name, String email, String password) async {
  try {
    final response = await ApiClient.instance.post('/auth/register', data: {
      'name': name,
      'email': email,
      'password': password,
    });
    
    if (response.data['success']) {
      // Registration successful
      print('User ID: ${response.data['data']['user_id']}');
    }
  } on DioException catch (e) {
    final message = e.response?.data['message'] ?? 'Registration failed';
    throw Exception(message);
  }
}
```

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123"
}
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "user_id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

---

### Login
```dart
// POST /auth/login
Future<Map<String, dynamic>> login(String email, String password) async {
  try {
    final response = await ApiClient.instance.post('/auth/login', data: {
      'email': email,
      'password': password,
    });
    
    if (response.data['success']) {
      final data = response.data['data'];
      
      // Simpan token
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('token', data['token']);
      
      return data['user'];
    }
    throw Exception('Login failed');
  } on DioException catch (e) {
    final message = e.response?.data['message'] ?? 'Login failed';
    throw Exception(message);
  }
}
```

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "abc123...",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    }
  }
}
```

---

## 3. Profile Endpoint

### Get User Profile
```dart
// GET /profile/user
// Requires: Authorization header
Future<Map<String, dynamic>> getProfile() async {
  try {
    final response = await ApiClient.instance.get('/profile/user');
    
    if (response.data['success']) {
      return response.data['data'];
    }
    throw Exception('Failed to get profile');
  } on DioException catch (e) {
    throw Exception(e.response?.data['message'] ?? 'Failed');
  }
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Profile retrieved",
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

---

## 4. Dashboard Endpoint

### Get Dashboard Summary
```dart
// GET /dashboard/summary
// Requires: Authorization header
Future<Map<String, dynamic>> getDashboardSummary() async {
  try {
    final response = await ApiClient.instance.get('/dashboard/summary');
    
    if (response.data['success']) {
      return response.data['data'];
      // {
      //   "total_goals": 3,
      //   "total_target": 15000000,
      //   "total_saved": 5000000,
      //   "overall_progress": 33.33
      // }
    }
    throw Exception('Failed');
  } on DioException catch (e) {
    throw Exception(e.response?.data['message'] ?? 'Failed');
  }
}
```

---

## 5. Goals Endpoints

### Get All Goals
```dart
// GET /goals/index
// Requires: Authorization header
Future<List<Map<String, dynamic>>> getGoals() async {
  try {
    final response = await ApiClient.instance.get('/goals/index');
    
    if (response.data['success']) {
      return List<Map<String, dynamic>>.from(response.data['data']);
    }
    throw Exception('Failed');
  } on DioException catch (e) {
    throw Exception(e.response?.data['message'] ?? 'Failed');
  }
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Goals retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "New Laptop",
      "target_amount": 10000000,
      "current_amount": 2500000,
      "deadline": "2024-12-31",
      "description": "MacBook Pro",
      "progress_percentage": 25.0
    }
  ]
}
```

---

### Create Goal
```dart
// POST /goals/store
// Requires: Authorization header
Future<Map<String, dynamic>> createGoal({
  required String name,
  required double targetAmount,
  String? deadline,
  String? description,
}) async {
  try {
    final response = await ApiClient.instance.post('/goals/store', data: {
      'name': name,
      'target_amount': targetAmount,
      if (deadline != null) 'deadline': deadline,
      if (description != null) 'description': description,
    });
    
    if (response.data['success']) {
      return response.data['data'];
    }
    throw Exception('Failed to create goal');
  } on DioException catch (e) {
    throw Exception(e.response?.data['message'] ?? 'Failed');
  }
}
```

**Request Body:**
```json
{
  "name": "New Laptop",
  "target_amount": 10000000,
  "deadline": "2024-12-31",
  "description": "MacBook Pro M3"
}
```

---

### Update Goal
```dart
// PUT /goals/update
// Requires: Authorization header
Future<void> updateGoal({
  required int id,
  String? name,
  double? targetAmount,
  String? deadline,
  String? description,
}) async {
  try {
    await ApiClient.instance.put('/goals/update', data: {
      'id': id,
      if (name != null) 'name': name,
      if (targetAmount != null) 'target_amount': targetAmount,
      if (deadline != null) 'deadline': deadline,
      if (description != null) 'description': description,
    });
  } on DioException catch (e) {
    throw Exception(e.response?.data['message'] ?? 'Failed');
  }
}
```

---

### Delete Goal
```dart
// DELETE /goals/delete
// Requires: Authorization header
Future<void> deleteGoal(int id) async {
  try {
    await ApiClient.instance.delete('/goals/delete', data: {'id': id});
  } on DioException catch (e) {
    throw Exception(e.response?.data['message'] ?? 'Failed');
  }
}
```

---

## 6. Transactions Endpoints

### Get Transactions by Goal
```dart
// GET /transactions/index?goal_id={id}
// Requires: Authorization header
Future<List<Map<String, dynamic>>> getTransactions(int goalId) async {
  try {
    final response = await ApiClient.instance.get(
      '/transactions/index',
      queryParameters: {'goal_id': goalId},
    );
    
    if (response.data['success']) {
      return List<Map<String, dynamic>>.from(response.data['data']);
    }
    throw Exception('Failed');
  } on DioException catch (e) {
    throw Exception(e.response?.data['message'] ?? 'Failed');
  }
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "goal_id": 1,
      "amount": 500000,
      "description": "Weekly saving",
      "transaction_date": "2024-01-15 10:30:00"
    }
  ]
}
```

---

### Create Transaction (Add Saving / Deposit)
```dart
// POST /transactions/store
// Requires: Authorization header
Future<Map<String, dynamic>> addTransaction({
  required int goalId,
  required double amount,
  String method = 'manual', // 'dana', 'gopay', 'bank_transfer'
  String? description,
}) async {
  try {
    final response = await ApiClient.instance.post('/transactions/store', data: {
      'goal_id': goalId,
      'amount': amount,
      'method': method,
      if (description != null) 'description': description,
    });
    
    if (response.data['success']) {
      return response.data['data'];
    }
    throw Exception('Failed');
  } on DioException catch (e) {
    throw Exception(e.response?.data['message'] ?? 'Failed');
  }
}
```

**Request Body:**
```json
{
  "goal_id": 1,
  "amount": 500000,
  "method": "dana",
  "description": "Weekly saving"
}
```

---

### Delete Transaction
```dart
// DELETE /transactions/delete
// Requires: Authorization header
Future<void> deleteTransaction(int id) async {
  try {
    await ApiClient.instance.delete('/transactions/delete', data: {'id': id});
  } on DioException catch (e) {
    throw Exception(e.response?.data['message'] ?? 'Failed');
  }
}
```

---

## 7. Withdrawal Endpoints

### Request Withdrawal (Goal-Specific)
```dart
// POST /withdrawals/request
// Requires: Authorization header
// UPDATED: Now requires goal_id to specify which goal to withdraw from
Future<Map<String, dynamic>> requestWithdrawal({
  required int goalId,    // WAJIB: ID goal yang ingin ditarik
  required double amount,
  required String method,
  String? accountNumber,
  String? notes,
}) async {
  try {
    final response = await ApiClient.instance.post('/withdrawals/request', data: {
      'goal_id': goalId,
      'amount': amount,
      'method': method, // 'dana', 'gopay', 'bank_transfer', 'ovo', 'shopeepay'
      if (accountNumber != null) 'account_number': accountNumber,
      if (notes != null) 'notes': notes,
    });
    
    if (response.data['success']) {
      return response.data['data'];
    }
    throw Exception('Failed to request withdrawal');
  } on DioException catch (e) {
    throw Exception(e.response?.data['message'] ?? 'Failed');
  }
}
```

**Request Body:**
```json
{
  "goal_id": 1,
  "amount": 100000,
  "method": "dana",
  "account_number": "08123456789",
  "notes": "Penarikan untuk keperluan darurat"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Withdrawal request submitted successfully",
  "data": {
    "id": 5,
    "goal_id": 1,
    "goal_name": "Laptop",
    "amount": 100000,
    "method": "dana",
    "status": "pending",
    "goal_balance": 400000,
    "created_at": "2024-01-19 01:00:00"
  }
}
```

> **Note:** Jumlah penarikan tidak boleh melebihi saldo goal yang dipilih.

---

### Get Withdrawal History
```dart
// GET /withdrawals/index
// Requires: Authorization header
Future<Map<String, dynamic>> getWithdrawalHistory({String? status}) async {
  try {
    final Map<String, dynamic> query = {};
    if (status != null) query['status'] = status;

    final response = await ApiClient.instance.get(
      '/withdrawals/index',
      queryParameters: query,
    );
    
    if (response.data['success']) {
      return response.data['data'];
      // Returns: { "summary": {...}, "withdrawals": [...] }
    }
    throw Exception('Failed to get history');
  } on DioException catch (e) {
    throw Exception(e.response?.data['message'] ?? 'Failed');
  }
}
```

---


---

## 8. Notification Endpoints

### Get Notifications
```dart
// GET /notifications/index
// Requires: Authorization header
Future<List<Map<String, dynamic>>> getNotifications() async {
  try {
    final response = await ApiClient.instance.get('/notifications/index');
    
    if (response.data['success']) {
      return List<Map<String, dynamic>>.from(response.data['data']);
    }
    throw Exception('Failed to get notifications');
  } on DioException catch (e) {
    throw Exception(e.response?.data['message'] ?? 'Failed');
  }
}
```

**Response Structure:**
```json
[
  {
    "id": 1,
    "title": "Tabungan Berhasil",
    "message": "Tabungan sebesar Rp 50.000 berhasil...",
    "type": "deposit",
    "is_read": false,
    "created_at": "2024-01-18 10:00:00"
  }
]
```

### Usage in Notification Screen
```dart
import 'package:intl/intl.dart';

class NotificationScreen extends StatelessWidget {
  const NotificationScreen({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Notifikasi')),
      body: FutureBuilder<List<Map<String, dynamic>>>(
        future: getNotifications(),
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          
          if (snapshot.hasError) {
            return Center(child: Text('Error: ${snapshot.error}'));
          }
          
          final notifs = snapshot.data ?? [];
          
          if (notifs.isEmpty) {
            return const Center(child: Text('Belum ada notifikasi'));
          }
          
          return ListView.separated(
            itemCount: notifs.length,
            separatorBuilder: (c, i) => const Divider(height: 1),
            itemBuilder: (context, index) {
              final item = notifs[index];
              return ListTile(
                leading: CircleAvatar(
                  backgroundColor: item['type'] == 'deposit' 
                      ? Colors.green.shade100 
                      : Colors.orange.shade100,
                  child: Icon(
                    item['type'] == 'deposit' 
                        ? Icons.arrow_downward 
                        : Icons.arrow_upward,
                    color: item['type'] == 'deposit' 
                        ? Colors.green 
                        : Colors.orange,
                  ),
                ),
                title: Text(
                  item['title'],
                  style: TextStyle(fontWeight: item['is_read'] ? FontWeight.normal : FontWeight.bold),
                ),
                subtitle: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(item['message']),
                    const SizedBox(height: 4),
                    Text(
                      item['created_at'] ?? '',
                      style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                    ),
                  ],
                ),
              );
            },
          );
        },
      ),
    );
  }
}
```

---


### Firebase Push Notification Setup

**Dependency (`pubspec.yaml`):**
```yaml
dependencies:
  firebase_core: latest_version
  firebase_messaging: latest_version
```

**Implementation Code (Lengkap dengan Foreground Handler):**
Panggil fungsi `setupPushNotifications` ini di dalam controller dashboard atau setelah login sukses.

```dart
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart'; // Asumsi pakai GetX, jika tidak ganti dengan ScaffoldMessenger

Future<void> setupPushNotifications(int userId) async {
  final fcm = FirebaseMessaging.instance;
  
  // 1. Request Permission (Wajib untuk Android 13+)
  NotificationSettings settings = await fcm.requestPermission(
    alert: true,
    badge: true,
    sound: true,
  );
  
  if (settings.authorizationStatus == AuthorizationStatus.authorized) {
    print('✅ User granted permission');
    
    // 2. Subscribe to Topic
    String topic = 'user_$userId';
    await fcm.subscribeToTopic(topic);
    print("✅ Subscribed to topic: $topic");

    // 3. Handle Foreground Messages (Saat app dibuka)
    FirebaseMessaging.onMessage.listen((RemoteMessage message) {
      if (message.notification != null) {
        Get.snackbar(
          message.notification!.title ?? 'Notifikasi Baru',
          message.notification!.body ?? '',
          backgroundColor: Colors.white,
          onTap: (_) => Get.to(() => const NotificationScreen()),
          duration: const Duration(seconds: 4),
        );
      }
    });

    // 4. Handle Background Notification Click (Saat app di background & notif diklik)
    FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
      print('🚀 Notification clicked from background!');
      Get.to(() => const NotificationScreen());
    });

    // 5. Handle Terminated State (Saat app mati total & dibuka dari notif)
    fcm.getInitialMessage().then((RemoteMessage? message) {
      if (message != null) {
        print('🚀 App opened from terminated state by notification!');
        // Delay sedikit agar GetX siap
        Future.delayed(const Duration(seconds: 1), () {
          Get.to(() => const NotificationScreen());
        });
      }
    });

  } else {
    print('❌ User declined permission');
  }
}
```

**Cara Pakai (Contoh di Login Controller):**
```dart
// Setelah simpan token & user data
await setupPushNotifications(user['id']);
Get.offAll(() => DashboardScreen());
```

---

## 9. Overflow Allocation System

Sistem ini menangani skenario ketika user melakukan deposit yang **melebihi target goal**. Overflow **TIDAK** otomatis dialokasikan ke goal lain, melainkan user harus **manual memilih** kemana alokasi overflow tersebut.

### Flow Diagram
```
User deposit Rp 25 juta ke Goal A (target: Rp 15 juta)
                    ↓
        Goal A completed (Rp 15 juta)
                    ↓
        Overflow detected (Rp 10 juta)
                    ↓
    Show Allocation Dialog di Flutter
                    ↓
User pilih alokasi manual:
  - Rp 5 juta → Goal B
  - Rp 5 juta → Available Balance
                    ↓
    Call POST /transactions/allocate
```

---

### Step 1: Detect Overflow saat Deposit

Response dari endpoint `POST /transactions/store` sekarang include overflow info:

```dart
// POST /transactions/store
Future<Map<String, dynamic>> addTransaction({
  required int goalId,
  required double amount,
  String method = 'manual',
  String? description,
}) async {
  try {
    final response = await ApiClient.instance.post('/transactions/store', data: {
      'goal_id': goalId,
      'amount': amount,
      'method': method,
      if (description != null) 'description': description,
    });
    
    if (response.data['success']) {
      return response.data['data'];
      // NOW INCLUDES:
      // {
      //   "goal_completed": true,
      //   "deposited_amount": 15000000,
      //   "overflow_amount": 10000000
      // }
    }
    throw Exception('Failed');
  } on DioException catch (e) {
    throw Exception(e.response?.data['message'] ?? 'Failed');
  }
}
```

**Enhanced Response (201):**
```json
{
  "success": true,
  "message": "Transaction created successfully",
  "data": {
    "id": 10,
    "goal_id": 1,
    "amount": 25000000,
    "method": "bank_transfer",
    "description": "Deposit besar",
    "transaction_date": "2024-01-19 18:00:00",
    "created_at": "2024-01-19 18:00:00",
    "goal_completed": true,
    "deposited_amount": 15000000,
    "overflow_amount": 10000000
  }
}
```

---

### Step 2: Handle Overflow di Flutter

Setelah deposit berhasil, cek apakah ada overflow:

```dart
Future<void> handleDeposit(int goalId, double amount) async {
  try {
    final result = await addTransaction(
      goalId: goalId,
      amount: amount,
      method: 'bank_transfer',
    );
    
    // Cek jika ada overflow
    if (result['overflow_amount'] > 0) {
      // Tampilkan dialog untuk alokasi manual
      showOverflowAllocationDialog(
        context: context,
        overflowAmount: result['overflow_amount'],
        completedGoalName: 'Macbook', // dari state
      );
    } else {
      // Success tanpa overflow
      Get.snackbar('Berhasil', 'Deposit berhasil ditambahkan');
      // Refresh goal list
    }
  } catch (e) {
    Get.snackbar('Error', e.toString());
  }
}
```

---

### Step 3: Overflow Allocation Dialog

Dialog untuk user input manual allocation:

```dart
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

class OverflowAllocationDialog extends StatefulWidget {
  final double overflowAmount;
  final String completedGoalName;
  
  const OverflowAllocationDialog({
    Key? key,
    required this.overflowAmount,
    required this.completedGoalName,
  }) : super(key: key);

  @override
  State<OverflowAllocationDialog> createState() => _OverflowAllocationDialogState();
}

class _OverflowAllocationDialogState extends State<OverflowAllocationDialog> {
  final NumberFormat currencyFormat = NumberFormat.currency(
    locale: 'id_ID',
    symbol: 'Rp ',
    decimalDigits: 0,
  );
  
  List<Map<String, dynamic>> goals = []; // Fetch from API
  Map<int, TextEditingController> controllers = {};
  double remainingOverflow = 0;
  bool isLoading = true;
  
  @override
  void initState() {
    super.initState();
    remainingOverflow = widget.overflowAmount;
    fetchAvailableGoals();
  }
  
  Future<void> fetchAvailableGoals() async {
    try {
      // Fetch goals yang belum completed
      final allGoals = await getGoals();
      goals = allGoals.where((g) {
        final current = g['current_amount'] as double;
        final target = g['target_amount'] as double;
        return current < target; // Hanya goal yang belum completed
      }).toList();
      
      // Initialize controllers
      for (var goal in goals) {
        controllers[goal['id']] = TextEditingController();
      }
      
      setState(() => isLoading = false);
    } catch (e) {
      setState(() => isLoading = false);
      Navigator.pop(context);
      Get.snackbar('Error', 'Gagal memuat goal: $e');
    }
  }
  
  void calculateRemaining() {
    double total = 0;
    for (var controller in controllers.values) {
      final value = double.tryParse(controller.text.replaceAll('.', '')) ?? 0;
      total += value;
    }
    setState(() {
      remainingOverflow = widget.overflowAmount - total;
    });
  }
  
  Future<void> submitAllocation() async {
    try {
      // Build allocations array
      List<Map<String, dynamic>> allocations = [];
      
      for (var goal in goals) {
        final controller = controllers[goal['id']]!;
        final amount = double.tryParse(controller.text.replaceAll('.', '')) ?? 0;
        
        if (amount > 0) {
          // Validate tidak melebihi remaining target
          final remaining = goal['target_amount'] - goal['current_amount'];
          if (amount > remaining) {
            Get.snackbar(
              'Error',
              'Jumlah untuk "${goal['name']}" melebihi sisa target (${currencyFormat.format(remaining)})',
            );
            return;
          }
          
          allocations.add({
            'goal_id': goal['id'],
            'amount': amount,
          });
        }
      }
      
      if (allocations.isEmpty && remainingOverflow == widget.overflowAmount) {
        Get.snackbar('Info', 'Silakan alokasikan minimal ke satu goal atau simpan sebagai balance');
        return;
      }
      
      // Call allocation endpoint
      await allocateOverflow(
        allocations: allocations,
        saveToBalanceAmount: remainingOverflow > 0 ? remainingOverflow : null,
      );
      
      Navigator.pop(context);
      Get.snackbar('Berhasil', 'Overflow berhasil dialokasikan!');
      
      // Refresh goal list
      
    } catch (e) {
      Get.snackbar('Error', 'Gagal mengalokasikan: $e');
    }
  }
  
  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Goal Tercapai! 🎉'),
          const SizedBox(height: 4),
          Text(
            'Goal "${widget.completedGoalName}" telah selesai',
            style: Theme.of(context).textTheme.bodySmall,
          ),
        ],
      ),
      content: isLoading
          ? const Center(child: CircularProgressIndicator())
          : goals.isEmpty
              // CASE: All goals completed - offer withdrawal option
              ? Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.green.shade50,
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.celebration, color: Colors.green),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              'Selamat! Semua goal telah tercapai! 🎉',
                              style: const TextStyle(
                                fontSize: 14,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                    Text(
                      'Anda memiliki sisa dana:',
                      style: TextStyle(color: Colors.grey[700]),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      currencyFormat.format(widget.overflowAmount),
                      style: const TextStyle(
                        fontSize: 24,
                        fontWeight: FontWeight.bold,
                        color: Colors.green,
                      ),
                    ),
                    const SizedBox(height: 20),
                    const Text(
                      'Pilih tindakan:',
                      style: TextStyle(fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(height: 12),
                    
                    // Option 1: Save as Balance
                    Card(
                      child: ListTile(
                        leading: const Icon(Icons.account_balance_wallet, color: Colors.blue),
                        title: const Text('Simpan di Akun'),
                        subtitle: const Text('Gunakan untuk goal baru atau deposit nanti'),
                        trailing: const Icon(Icons.chevron_right),
                        onTap: () async {
                          try {
                            // Save to available balance
                            await allocateOverflow(
                              allocations: [],
                              saveToBalanceAmount: widget.overflowAmount,
                            );
                            Navigator.pop(context);
                            Get.snackbar(
                              'Berhasil',
                              'Dana disimpan sebagai Available Balance',
                              backgroundColor: Colors.green.shade100,
                            );
                          } catch (e) {
                            Get.snackbar('Error', 'Gagal menyimpan: $e');
                          }
                        },
                      ),
                    ),
                    const SizedBox(height: 8),
                    
                    // Option 2: Withdraw to E-Wallet
                    Card(
                      child: ListTile(
                        leading: const Icon(Icons.payment, color: Colors.orange),
                        title: const Text('Tarik ke E-Wallet'),
                        subtitle: const Text('Transfer ke Dana, GoPay, OVO, dll'),
                        trailing: const Icon(Icons.chevron_right),
                        onTap: () async {
                          try {
                            // CRITICAL: Save to balance FIRST before withdrawing
                            // This ensures the funds exist in available_balance
                            await allocateOverflow(
                              allocations: [],
                              saveToBalanceAmount: widget.overflowAmount,
                            );
                            
                            Navigator.pop(context); // Close dialog
                          
                            // Navigate to withdrawal screen with prefilled amount
                            Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (_) => WithdrawalScreen(
                                  prefilledAmount: widget.overflowAmount,
                                  fromOverflow: true,
                                ),
                              ),
                            );
                          } catch (e) {
                            Get.snackbar('Error', 'Gagal memproses: $e');
                          }
                        },
                      ),
                    ),
                  ],
                )
              // CASE: Goals available for allocation
              : SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.green.shade50,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.info_outline, color: Colors.green),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            'Anda memiliki sisa ${currencyFormat.format(widget.overflowAmount)} untuk dialokasikan',
                            style: const TextStyle(fontSize: 13),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                  
                  if (goals.isEmpty)
                    const Text('Tidak ada goal lain yang tersedia')
                  else
                    ...goals.map((goal) {
                      final remaining = goal['target_amount'] - goal['current_amount'];
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 12),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              goal['name'],
                              style: const TextStyle(fontWeight: FontWeight.bold),
                            ),
                            Text(
                              'Sisa target: ${currencyFormat.format(remaining)}',
                              style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                            ),
                            const SizedBox(height: 4),
                            TextField(
                              controller: controllers[goal['id']],
                              keyboardType: TextInputType.number,
                              decoration: InputDecoration(
                                hintText: '0',
                                prefixText: 'Rp ',
                                suffixText: ' (max ${currencyFormat.format(remaining)})',
                                border: const OutlineInputBorder(),
                                contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                              ),
                              onChanged: (_) => calculateRemaining(),
                            ),
                          ],
                        ),
                      );
                    }).toList(),
                  
                  const SizedBox(height: 12),
                  const Divider(),
                  const SizedBox(height: 8),
                  
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text('Sisa yang akan disimpan:'),
                      Text(
                        currencyFormat.format(remainingOverflow),
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          color: remainingOverflow < 0 ? Colors.red : Colors.green,
                        ),
                      ),
                    ],
                  ),
                  if (remainingOverflow < 0)
                    Padding(
                      padding: const EdgeInsets.only(top: 8),
                      child: Text(
                        '⚠️ Total alokasi melebihi overflow!',
                        style: TextStyle(color: Colors.red[700], fontSize: 12),
                      ),
                    ),
                ],
              ),
            ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: const Text('Batal'),
        ),
        ElevatedButton(
          onPressed: remainingOverflow < 0 ? null : submitAllocation,
          child: const Text('Alokasikan'),
        ),
      ],
    );
  }
  
  @override
  void dispose() {
    for (var controller in controllers.values) {
      controller.dispose();
    }
    super.dispose();
  }
}

// Helper function to show dialog
void showOverflowAllocationDialog({
  required BuildContext context,
  required double overflowAmount,
  required String completedGoalName,
}) {
  showDialog(
    context: context,
    barrierDismissible: false,
    builder: (context) => OverflowAllocationDialog(
      overflowAmount: overflowAmount,
      completedGoalName: completedGoalName,
    ),
  );
}
```

---

### Step 4: Call Allocate Overflow Endpoint

```dart
// POST /transactions/allocate
Future<Map<String, dynamic>> allocateOverflow({
  required List<Map<String, dynamic>> allocations,
  double? saveToBalanceAmount,
}) async {
  try {
    final response = await ApiClient.instance.post('/transactions/allocate', data: {
      'allocations': allocations,
      if (saveToBalanceAmount != null) 'save_to_balance_amount': saveToBalanceAmount,
    });
    
    if (response.data['success']) {
      return response.data['data'];
    }
    throw Exception('Failed to allocate');
  } on DioException catch (e) {
    throw Exception(e.response?.data['message'] ?? 'Failed');
  }
}
```

**Request Example:**
```json
{
  "allocations": [
    {
      "goal_id": 2,
      "amount": 5000000
    }
  ],
  "save_to_balance_amount": 3000000
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Overflow successfully allocated",
  "data": {
    "allocated": [
      {
        "goal_id": 2,
        "goal_name": "HP",
        "amount": 5000000,
        "goal_completed": true,
        "overflow_amount": 0
      },
      {
        "goal_id": 3,
        "goal_name": "Motor",
        "amount": 3000000,
        "goal_completed": false,
        "overflow_amount": 0
      }
    ],
    "available_balance": 2000000,
    "total_allocated": 8000000
  }
}
```

---

### Step 5: Display Available Balance (Optional)

Jika Anda ingin menampilkan available balance di dashboard:

```dart
// Tambahkan di Dashboard Summary atau Profile
class DashboardCard extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return FutureBuilder<Map<String, dynamic>>(
      future: getProfile(), // atau getDashboardSummary()
      builder: (context, snapshot) {
        if (snapshot.hasData) {
          final user = snapshot.data!;
          final availableBalance = user['available_balance'] ?? 0;
          
          return Card(
            child: ListTile(
              leading: const Icon(Icons.account_balance_wallet),
              title: const Text('Available Balance'),
              subtitle: const Text('Sisa dana yang belum dialokasikan'),
              trailing: Text(
                currencyFormat.format(availableBalance),
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: Colors.green,
                ),
              ),
            ),
          );
        }
        return const CircularProgressIndicator();
      },
    );
  }
}
```

---

### Key Points 🎯

1. **Manual Allocation Only**: User harus memilih berapa amount ke goal mana
2. **Validation**: Frontend harus validasi amount tidak melebihi goal's remaining target
3. **Remaining Balance**: Sisa yang tidak dialokasikan disimpan sebagai `available_balance` di user
4. **Goal Completion**: Backend otomatis detect dan complete goal jika target tercapai
5. **Multiple Allocations**: User bisa alokasikan overflow ke beberapa goal sekaligus
6. **🆕 All Goals Completed**: Ketika semua goal sudah selesai, user diberi 2 pilihan:
   - **Simpan di Akun** → Dana masuk `available_balance` untuk digunakan nanti
   - **Tarik ke E-Wallet** → Langsung redirect ke withdrawal screen dengan amount sudah terisi

---

### Enhancement: Withdrawal Screen dengan Prefilled Amount

Untuk support withdrawal dari overflow, `WithdrawalScreen` perlu accept 2 parameter opsional:

```dart
class WithdrawalScreen extends StatefulWidget {
  final double? prefilledAmount;  // ← Amount dari overflow
  final bool fromOverflow;         // ← Flag untuk tracking

  const WithdrawalScreen({
    Key? key,
    this.prefilledAmount,
    this.fromOverflow = false,
  }) : super(key: key);

  @override
  State<WithdrawalScreen> createState() => _WithdrawalScreenState();
}

class _WithdrawalScreenState extends State<WithdrawalScreen> {
  late TextEditingController _amountController;
  
  @override
  void initState() {
    super.initState();
    // Pre-fill amount jika dari overflow
    _amountController = TextEditingController(
      text: widget.prefilledAmount != null 
          ? widget.prefilledAmount!.toStringAsFixed(0)
          : '',
    );
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.fromOverflow 
            ? 'Tarik Dana Overflow' 
            : 'Penarikan Dana'
        ),
      ),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            if (widget.fromOverflow)
              Container(
                padding: const EdgeInsets.all(12),
                margin: const EdgeInsets.only(bottom: 16),
                decoration: BoxDecoration(
                  color: Colors.blue.shade50,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Row(
                  children: [
                    Icon(Icons.info_outline, color: Colors.blue[700]),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'Dana dari goal yang telah selesai',
                        style: TextStyle(color: Colors.blue[700]),
                      ),
                    ),
                  ],
                ),
              ),
            
            // Amount field dengan nilai pre-filled
            TextFormField(
              controller: _amountController,
              decoration: const InputDecoration(
                labelText: 'Jumlah Penarikan',
                prefixText: 'Rp ',
                border: OutlineInputBorder(),
              ),
              keyboardType: TextInputType.number,
              enabled: !widget.fromOverflow, // Disable jika dari overflow
            ),
            
            // ... rest of withdrawal form
          ],
        ),
      ),
    );
  }
}
```

**Perhatikan:**
- `prefilledAmount` → Amount dari overflow yang auto-filled
- `fromOverflow` → Flag untuk UI customization (banner info, disable amount field, dll)
- Amount field **disabled** jika dari overflow agar user tidak ubah nilai

---

## 10. Local Photo Storage


### Save Profile Photo
```dart
import 'package:image_picker/image_picker.dart';
import 'package:path_provider/path_provider.dart';
import 'dart:io';

Future<String?> pickAndSaveProfilePhoto() async {
  final picker = ImagePicker();
  final XFile? image = await picker.pickImage(source: ImageSource.gallery);
  
  if (image == null) return null;
  
  // Get app directory
  final appDir = await getApplicationDocumentsDirectory();
  final fileName = 'profile_photo.jpg';
  final savedPath = '${appDir.path}/$fileName';
  
  // Copy image to app directory
  await File(image.path).copy(savedPath);
  
  // Save path to SharedPreferences
  final prefs = await SharedPreferences.getInstance();
  await prefs.setString('profile_photo_path', savedPath);
  
  return savedPath;
}

Future<String?> getProfilePhotoPath() async {
  final prefs = await SharedPreferences.getInstance();
  return prefs.getString('profile_photo_path');
}
```

### Save Goal Photo
```dart
Future<String?> pickAndSaveGoalPhoto(int goalId) async {
  final picker = ImagePicker();
  final XFile? image = await picker.pickImage(source: ImageSource.gallery);
  
  if (image == null) return null;
  
  final appDir = await getApplicationDocumentsDirectory();
  final fileName = 'goal_$goalId.jpg';
  final savedPath = '${appDir.path}/$fileName';
  
  await File(image.path).copy(savedPath);
  
  final prefs = await SharedPreferences.getInstance();
  await prefs.setString('goal_image_$goalId', savedPath);
  
  return savedPath;
}

Future<String?> getGoalPhotoPath(int goalId) async {
  final prefs = await SharedPreferences.getInstance();
  return prefs.getString('goal_image_$goalId');
}
```

---

## 10. Error Response Format

Semua error response mengikuti format:
```json
{
  "success": false,
  "message": "Error description"
}
```

| Status Code | Meaning |
|-------------|---------|
| 400 | Bad Request - validation error |
| 401 | Unauthorized - token invalid/expired |
| 404 | Not Found |
| 405 | Method Not Allowed |
| 409 | Conflict - duplicate data |
| 500 | Server Error |

---

## 11. Form Validation

### `lib/core/validators.dart`
```dart
class Validators {
  // Email validation
  static String? validateEmail(String? value) {
    if (value == null || value.isEmpty) {
      return 'Email wajib diisi';
    }
    final emailRegex = RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$');
    if (!emailRegex.hasMatch(value)) {
      return 'Format email tidak valid';
    }
    return null;
  }

  // Password validation
  static String? validatePassword(String? value) {
    if (value == null || value.isEmpty) {
      return 'Password wajib diisi';
    }
    if (value.length < 6) {
      return 'Password minimal 6 karakter';
    }
    return null;
  }

  // Confirm password validation
  static String? validateConfirmPassword(String? value, String password) {
    if (value == null || value.isEmpty) {
      return 'Konfirmasi password wajib diisi';
    }
    if (value != password) {
      return 'Password tidak cocok';
    }
    return null;
  }

  // Name validation
  static String? validateName(String? value) {
    if (value == null || value.isEmpty) {
      return 'Nama wajib diisi';
    }
    if (value.length < 3) {
      return 'Nama minimal 3 karakter';
    }
    return null;
  }

  // Goal name validation
  static String? validateGoalName(String? value) {
    if (value == null || value.isEmpty) {
      return 'Nama goal wajib diisi';
    }
    return null;
  }

  // Amount validation (for target_amount & transaction amount)
  static String? validateAmount(String? value) {
    if (value == null || value.isEmpty) {
      return 'Jumlah wajib diisi';
    }
    final amount = double.tryParse(value.replaceAll(',', '').replaceAll('.', ''));
    if (amount == null) {
      return 'Masukkan angka yang valid';
    }
    if (amount <= 0) {
      return 'Jumlah harus lebih dari 0';
    }
    return null;
  }

  // Required field validation
  static String? validateRequired(String? value, String fieldName) {
    if (value == null || value.isEmpty) {
      return '$fieldName wajib diisi';
    }
    return null;
  }
}
```

---


### Usage in Withdrawal Screen

```dart
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

class WithdrawalScreen extends StatefulWidget {
  final double? prefilledAmount;
  final bool fromOverflow;

  const WithdrawalScreen({
    Key? key,
    this.prefilledAmount,
    this.fromOverflow = false,
  }) : super(key: key);

  @override
  State<WithdrawalScreen> createState() => _WithdrawalScreenState();
}

class _WithdrawalScreenState extends State<WithdrawalScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _amountCtrl;
  final _accountCtrl = TextEditingController();
  final _notesCtrl = TextEditingController();
  
  String _selectedMethod = 'dana';
  final List<String> _methods = ['dana', 'gopay', 'bank_transfer', 'ovo', 'shopeepay'];
  
  bool _isLoading = false;
  double _totalSavings = 0;

  @override
  void initState() {
    super.initState();
    _amountCtrl = TextEditingController(
      text: widget.prefilledAmount != null 
          ? widget.prefilledAmount!.toStringAsFixed(0) 
          : ''
    );
    _fetchBalance();
  }

  void _fetchBalance() async {
    // Implementasi fetch dashboard untuk dapat total saldo
    try {
      final summary = await getDashboardSummary();
      setState(() {
        _totalSavings = (summary['total_saved'] as num).toDouble();
      });
    } catch (e) {
      print('Error loading balance: $e');
    }
  }

  void _submit() async {
    if (!_formKey.currentState!.validate()) return;
    
    setState(() => _isLoading = true);
    
    try {
      final amount = double.parse(_amountCtrl.text.replaceAll('.', '').replaceAll(',', ''));
      
      // Validasi manual saldo
      if (amount > _totalSavings) {
        throw Exception('Saldo tidak mencukupi');
      }

      await requestWithdrawal(
        amount: amount,
        method: _selectedMethod,
        accountNumber: _accountCtrl.text,
        notes: _notesCtrl.text.isNotEmpty ? _notesCtrl.text : null,
      );
      
      if (!mounted) return;
      
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Permintaan penarikan berhasil dikirim'),
          backgroundColor: Colors.green,
        ),
      );
      Navigator.pop(context); // Kembali ke dashboard
      
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(e.toString().replaceAll('Exception: ', '')),
          backgroundColor: Colors.red,
        ),
      );
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.fromOverflow 
            ? 'Tarik Dana Overflow' 
            : 'Tarik Saldo'
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Info Banner for Overflow
              if (widget.fromOverflow)
                Container(
                  padding: const EdgeInsets.all(12),
                  margin: const EdgeInsets.only(bottom: 16),
                  decoration: BoxDecoration(
                    color: Colors.blue.shade50,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    children: [
                      Icon(Icons.info_outline, color: Colors.blue[700]),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          'Dana dari goal yang telah selesai',
                          style: TextStyle(color: Colors.blue[700]),
                        ),
                      ),
                    ],
                  ),
                )
              else
                // Info Saldo (Only show for normal withdrawal)
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.blue.shade50,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: Colors.blue.shade200),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.account_balance_wallet, color: Colors.blue),
                      const SizedBox(width: 12),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Total Tabungan', style: TextStyle(color: Colors.grey)),
                          Text(
                            'Rp ${_formatcurrency(_totalSavings)}',
                            style: const TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: Colors.blue,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              const SizedBox(height: 24),
              
              // Form Input
              DropdownButtonFormField<String>(
                value: _selectedMethod,
                decoration: const InputDecoration(
                  labelText: 'Metode Penarikan',
                  border: OutlineInputBorder(),
                ),
                items: _methods.map((m) {
                  return DropdownMenuItem(
                    value: m,
                    child: Text(m.toUpperCase().replaceAll('_', ' ')),
                  );
                }).toList(),
                onChanged: (val) => setState(() => _selectedMethod = val!),
              ),
              const SizedBox(height: 16),
              
              TextFormField(
                controller: _accountCtrl,
                decoration: const InputDecoration(
                  labelText: 'Nomor Rekening / E-Wallet',
                  border: OutlineInputBorder(),
                  prefixIcon: Icon(Icons.credit_card),
                ),
                validator: (val) => val == null || val.isEmpty ? 'Wajib diisi' : null,
              ),
              const SizedBox(height: 16),
              
              TextFormField(
                controller: _amountCtrl,
                enabled: !widget.fromOverflow, // Disable if from overflow
                decoration: const InputDecoration(
                  labelText: 'Jumlah Penarikan',
                  prefixText: 'Rp ',
                  border: OutlineInputBorder(),
                ),
                keyboardType: TextInputType.number,
                validator: Validators.validateAmount,
              ),
              const SizedBox(height: 16),
              
              TextFormField(
                controller: _notesCtrl,
                decoration: const InputDecoration(
                  labelText: 'Catatan (Opsional)',
                  border: OutlineInputBorder(),
                ),
                maxLines: 2,
              ),
              const SizedBox(height: 24),
              
              SizedBox(
                width: double.infinity,
                height: 50,
                child: ElevatedButton(
                  onPressed: _isLoading ? null : _submit,
                  child: _isLoading 
                      ? const CircularProgressIndicator() 
                      : const Text('Kirim Permintaan'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
  
  String _formatcurrency(double amount) {
    // Simple formatter, use intl package in production
    return amount.toStringAsFixed(0).replaceAllMapped(
      RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), 
      (Match m) => '${m[1]}.'
    );
  }
}
```

---


### Usage in Deposit Screen (Menabung)

```dart
import 'package:flutter/material.dart';

class DepositScreen extends StatefulWidget {
  final int goalId;
  final String goalName;

  const DepositScreen({
    Key? key, 
    required this.goalId,
    required this.goalName
  }) : super(key: key);

  @override
  State<DepositScreen> createState() => _DepositScreenState();
}

class _DepositScreenState extends State<DepositScreen> {
  final _formKey = GlobalKey<FormState>();
  final _amountCtrl = TextEditingController();
  final _descCtrl = TextEditingController();
  
  String _selectedMethod = 'manual';
  final List<String> _methods = ['manual', 'dana', 'gopay', 'bank_transfer', 'ovo'];
  
  bool _isLoading = false;

  void _submit() async {
    if (!_formKey.currentState!.validate()) return;
    
    setState(() => _isLoading = true);
    
    try {
      final amount = double.parse(_amountCtrl.text.replaceAll('.', '').replaceAll(',', ''));
      
      // UPDATED: Now receives overflow info
      final result = await addTransaction(
        goalId: widget.goalId,
        amount: amount,
        method: _selectedMethod,
        description: _descCtrl.text.isNotEmpty ? _descCtrl.text : 'Deposit via $_selectedMethod',
      );
      
      if (!mounted) return;
      
      // UPDATED: Check for overflow
      if (result['overflow_amount'] != null && result['overflow_amount'] > 0) {
        // Goal completed with overflow
        Navigator.pop(context, true); // Close deposit screen first
        
        // Show overflow allocation dialog
        showOverflowAllocationDialog(
          context: context,
          overflowAmount: result['overflow_amount'],
          completedGoalName: widget.goalName,
        );
      } else {
        // Normal deposit without overflow
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              result['goal_completed'] == true 
                ? '🎉 Goal tercapai! Tabungan berhasil!'
                : 'Berhasil menabung! 💰'
            ),
            backgroundColor: Colors.green,
          ),
        );
        Navigator.pop(context, true); // Return true to refresh goal list
      }
      
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(e.toString().replaceAll('Exception: ', '')),
          backgroundColor: Colors.red,
        ),
      );
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Menabung: ${widget.goalName}')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            children: [
              // Amount Input
              TextFormField(
                controller: _amountCtrl,
                decoration: const InputDecoration(
                  labelText: 'Nominal Tabungan',
                  prefixText: 'Rp ',
                  border: OutlineInputBorder(),
                ),
                keyboardType: TextInputType.number,
                validator: Validators.validateAmount,
              ),
              const SizedBox(height: 16),
              
              // Method Selection
              DropdownButtonFormField<String>(
                value: _selectedMethod,
                decoration: const InputDecoration(
                  labelText: 'Sumber Dana',
                  border: OutlineInputBorder(),
                ),
                items: _methods.map((m) {
                  return DropdownMenuItem(
                    value: m,
                    child: Row(
                      children: [
                        Icon(
                          m == 'manual' ? Icons.money : Icons.account_balance_wallet,
                          color: Colors.blue,
                          size: 20
                        ),
                        const SizedBox(width: 8),
                        Text(m.toUpperCase().replaceAll('_', ' ')),
                      ],
                    ),
                  );
                }).toList(),
                onChanged: (val) => setState(() => _selectedMethod = val!),
              ),
              const SizedBox(height: 16),
              
              TextFormField(
                controller: _descCtrl,
                decoration: const InputDecoration(
                  labelText: 'Catatan (Opsional)',
                  hintText: 'Misal: Sisa uang jajan',
                  border: OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 24),
              
              SizedBox(
                width: double.infinity,
                height: 50,
                child: ElevatedButton(
                  onPressed: _isLoading ? null : _submit,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.green,
                    foregroundColor: Colors.white,
                  ),
                  child: _isLoading 
                      ? const CircularProgressIndicator(color: Colors.white) 
                      : const Text('Simpan Tabungan'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
```

---

### Usage in Register Screen
```dart
class _RegisterScreenState extends State<RegisterScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _passCtrl = TextEditingController();
  final _confirmPassCtrl = TextEditingController();

  void _submit() async {
    if (!_formKey.currentState!.validate()) return;
    
    try {
      await register(_nameCtrl.text, _emailCtrl.text, _passCtrl.text);
      // Success - navigate to login
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString())),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Register')),
      body: Form(
        key: _formKey,
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            children: [
              TextFormField(
                controller: _nameCtrl,
                decoration: const InputDecoration(labelText: 'Nama'),
                validator: Validators.validateName,
              ),
              TextFormField(
                controller: _emailCtrl,
                decoration: const InputDecoration(labelText: 'Email'),
                keyboardType: TextInputType.emailAddress,
                validator: Validators.validateEmail,
              ),
              TextFormField(
                controller: _passCtrl,
                decoration: const InputDecoration(labelText: 'Password'),
                obscureText: true,
                validator: Validators.validatePassword,
              ),
              TextFormField(
                controller: _confirmPassCtrl,
                decoration: const InputDecoration(labelText: 'Konfirmasi Password'),
                obscureText: true,
                validator: (v) => Validators.validateConfirmPassword(v, _passCtrl.text),
              ),
              const SizedBox(height: 20),
              ElevatedButton(
                onPressed: _submit,
                child: const Text('Register'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
```

---

### Usage in Login Screen
```dart
class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailCtrl = TextEditingController();
  final _passCtrl = TextEditingController();

  void _submit() async {
    if (!_formKey.currentState!.validate()) return;
    
    try {
      await login(_emailCtrl.text, _passCtrl.text);
      // Navigate to dashboard
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString())),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Login')),
      body: Form(
        key: _formKey,
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            children: [
              TextFormField(
                controller: _emailCtrl,
                decoration: const InputDecoration(labelText: 'Email'),
                keyboardType: TextInputType.emailAddress,
                validator: Validators.validateEmail,
              ),
              TextFormField(
                controller: _passCtrl,
                decoration: const InputDecoration(labelText: 'Password'),
                obscureText: true,
                validator: Validators.validatePassword,
              ),
              const SizedBox(height: 20),
              ElevatedButton(
                onPressed: _submit,
                child: const Text('Login'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
```

---

### Usage in Add Goal Screen
```dart
class _AddGoalScreenState extends State<AddGoalScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameCtrl = TextEditingController();
  final _targetCtrl = TextEditingController();
  final _descCtrl = TextEditingController();

  void _submit() async {
    if (!_formKey.currentState!.validate()) return;
    
    try {
      final target = double.parse(_targetCtrl.text.replaceAll('.', '').replaceAll(',', ''));
      await createGoal(
        name: _nameCtrl.text,
        targetAmount: target,
        description: _descCtrl.text.isNotEmpty ? _descCtrl.text : null,
      );
      Navigator.pop(context);
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString())),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Buat Goal Baru')),
      body: Form(
        key: _formKey,
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            children: [
              TextFormField(
                controller: _nameCtrl,
                decoration: const InputDecoration(labelText: 'Nama Goal'),
                validator: Validators.validateGoalName,
              ),
              TextFormField(
                controller: _targetCtrl,
                decoration: const InputDecoration(
                  labelText: 'Target Jumlah',
                  prefixText: 'Rp ',
                ),
                keyboardType: TextInputType.number,
                validator: Validators.validateAmount,
              ),
              TextFormField(
                controller: _descCtrl,
                decoration: const InputDecoration(labelText: 'Deskripsi (opsional)'),
              ),
              const SizedBox(height: 20),
              ElevatedButton(
                onPressed: _submit,
                child: const Text('Buat Goal'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
```

---

### Usage in Add Transaction Dialog
```dart
void _showAddTransactionDialog(BuildContext context, int goalId) {
  final formKey = GlobalKey<FormState>();
  final amountCtrl = TextEditingController();
  final descCtrl = TextEditingController();

  showDialog(
    context: context,
    builder: (ctx) => AlertDialog(
      title: const Text('Tambah Setoran'),
      content: Form(
        key: formKey,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextFormField(
              controller: amountCtrl,
              decoration: const InputDecoration(
                labelText: 'Jumlah',
                prefixText: 'Rp ',
              ),
              keyboardType: TextInputType.number,
              validator: Validators.validateAmount,
            ),
            TextFormField(
              controller: descCtrl,
              decoration: const InputDecoration(labelText: 'Deskripsi (opsional)'),
            ),
          ],
        ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(ctx),
          child: const Text('Batal'),
        ),
        ElevatedButton(
          onPressed: () async {
            if (!formKey.currentState!.validate()) return;
            
            final amount = double.parse(amountCtrl.text.replaceAll('.', '').replaceAll(',', ''));
            try {
              await addTransaction(
                goalId: goalId,
                amount: amount,
                description: descCtrl.text.isNotEmpty ? descCtrl.text : null,
              );
              Navigator.pop(ctx);
              // Refresh goals list
            } catch (e) {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(content: Text(e.toString())),
              );
            }
          },
          child: const Text('Simpan'),
        ),
      ],
    ),
  );
}
```

---

## 10. Validation Summary

| Field | Validasi |
|-------|----------|
| `name` | Wajib, min 3 karakter |
| `email` | Wajib, format email valid |
| `password` | Wajib, min 6 karakter |
| `confirm_password` | Wajib, harus sama dengan password |
| `goal_name` | Wajib |
| `target_amount` | Wajib, angka > 0 |
| `amount` (transaction) | Wajib, angka > 0 |
