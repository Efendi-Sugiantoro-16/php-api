# GoalMoney Frontend - Flutter Implementation Guide

This document provides a complete guide to implementing the frontend for the GoalMoney application using Flutter. It covers the project structure, dependencies, state management (Provider), key services, and UI implementation.

## 1. Project Structure

We will use a feature-first or layer-first scalable structure.

```
lib/
├── config/              # Configuration files
│   ├── routes.dart      # Route definitions
│   └── theme.dart       # App theme/colors
├── core/                # Core utilities
│   ├── api_client.dart  # Dio setup & Interceptors
│   └── constants.dart   # API URLs, shared constants
├── models/              # Data models (serialization)
│   ├── user.dart
│   ├── goal.dart
│   └── transaction.dart
├── providers/           # State Management
│   ├── auth_provider.dart
│   └── goal_provider.dart
├── screens/             # UI Screens
│   ├── auth/
│   │   ├── login_screen.dart
│   │   └── register_screen.dart
│   ├── dashboard/
│   │   └── dashboard_screen.dart
│   └── goals/
│       ├── goal_list_screen.dart
│       ├── goal_detail_screen.dart
│       └── add_goal_screen.dart
├── widgets/             # Reusable UI Components
│   ├── custom_text_field.dart
│   └── summary_card.dart
└── main.dart            # Entry point
```

---

## 2. Dependencies (`pubspec.yaml`)

Add these dependencies for State Management, API calls, and local storage.

```yaml
dependencies:
  flutter:
    sdk: flutter
  
  # State Management
  provider: ^6.1.1

  # Networking
  dio: ^5.3.3

  # Local Storage (for Token)
  shared_preferences: ^2.2.2

  # Formatting
  intl: ^0.19.0

dev_dependencies:
  flutter_test:
    sdk: flutter
  flutter_lints: ^3.0.0
```

---

## 3. Core Utilities

### `lib/core/constants.dart`

```dart
class AppConstants {
  // Use 10.0.2.2 for Android Emulator to access localhost
  // Use localhost or your IP for iOS/Web
  static const String baseUrl = 'http://10.0.2.2:8001/api'; 
}
```

### `lib/core/api_client.dart`

Setup Dio with interceptors to automatically attach the Bearer token.

```dart
import 'package:dio/dio.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'constants.dart';

class ApiClient {
  final Dio _dio = Dio(
    BaseOptions(
      baseUrl: AppConstants.baseUrl,
      connectTimeout: const Duration(seconds: 10),
      receiveTimeout: const Duration(seconds: 10),
      headers: {'Content-Type': 'application/json'},
    ),
  );

  ApiClient() {
    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final prefs = await SharedPreferences.getInstance();
          finaltoken = prefs.getString('token');
          if (token != null) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          return handler.next(options);
        },
        onError: (DioException e, handler) {
          // Handle 401 Unauthorized globally if needed (e.g. logout)
          return handler.next(e);
        },
      ),
    );
  }

  Dio get dio => _dio;
}
```

---

## 4. Models

### `lib/models/user.dart`

```dart
class User {
  final int id;
  final String name;
  final String email;

  User({required this.id, required this.name, required this.email});

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      name: json['name'],
      email: json['email'],
    );
  }
}
```

### `lib/models/goal.dart`

```dart
class Goal {
  final int id;
  final String name;
  final double targetAmount;
  final double currentAmount;
  final String? deadline;
  final String? description;
  final dynamic progressPercentage; // Can be int or double from JSON

  Goal({
    required this.id,
    required this.name,
    required this.targetAmount,
    required this.currentAmount,
    this.deadline,
    this.description,
    this.progressPercentage,
  });

  factory Goal.fromJson(Map<String, dynamic> json) {
    return Goal(
      id: json['id'],
      name: json['name'],
      targetAmount: (json['target_amount'] as num).toDouble(),
      currentAmount: (json['current_amount'] as num).toDouble(),
      deadline: json['deadline'],
      description: json['description'],
      progressPercentage: json['progress_percentage'],
    );
  }

  double get progress => (progressPercentage is num) 
      ? (progressPercentage as num).toDouble() 
      : 0.0;
}
```

---

## 5. Providers (State Management)

### `lib/providers/auth_provider.dart`

```dart
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:dio/dio.dart';
import '../core/api_client.dart';
import '../models/user.dart';

enum AuthStatus { authenticated, unauthenticated, loading }

class AuthProvider with ChangeNotifier {
  final ApiClient _apiClient = ApiClient();
  AuthStatus _status = AuthStatus.loading;
  User? _user;
  String? _token;

  AuthStatus get status => _status;
  User? get user => _user;
  bool get isAuthenticated => _status == AuthStatus.authenticated;

  AuthProvider() {
    _loadUser();
  }

  Future<void> _loadUser() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString('token');

    if (_token != null) {
      try {
        await fetchProfile();
        _status = AuthStatus.authenticated;
      } catch (e) {
        _status = AuthStatus.unauthenticated;
        await prefs.remove('token');
      }
    } else {
      _status = AuthStatus.unauthenticated;
    }
    notifyListeners();
  }

  Future<void> login(String email, String password) async {
    try {
      final response = await _apiClient.dio.post('/auth/login', data: {
        'email': email,
        'password': password,
      });

      if (response.statusCode == 200) {
        final data = response.data['data'];
        _token = data['token'];
        _user = User.fromJson(data['user']);
        
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('token', _token!);
        
        _status = AuthStatus.authenticated;
        notifyListeners();
      }
    } catch (e) {
      rethrow;
    }
  }

  Future<void> register(String name, String email, String password) async {
    try {
      await _apiClient.dio.post('/auth/register', data: {
        'name': name,
        'email': email,
        'password': password,
      });
      // After register, user usually needs to login
    } catch (e) {
      rethrow;
    }
  }

  Future<void> fetchProfile() async {
    final response = await _apiClient.dio.get('/profile/user');
    if (response.statusCode == 200) {
      _user = User.fromJson(response.data['data']);
      notifyListeners();
    }
  }

  Future<void> logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('token');
    _token = null;
    _user = null;
    _status = AuthStatus.unauthenticated;
    notifyListeners();
  }
}
```

### `lib/providers/goal_provider.dart`

```dart
import 'package:flutter/material.dart';
import '../core/api_client.dart';
import '../models/goal.dart';

class GoalProvider with ChangeNotifier {
  final ApiClient _apiClient = ApiClient();
  List<Goal> _goals = [];
  Map<String, dynamic>? _dashboardSummary;
  bool _isLoading = false;

  List<Goal> get goals => _goals;
  Map<String, dynamic>? get summary => _dashboardSummary;
  bool get isLoading => _isLoading;

  Future<void> fetchGoals() async {
    _isLoading = true;
    notifyListeners();
    try {
      final response = await _apiClient.dio.get('/goals/index');
      if (response.statusCode == 200) {
        final List data = response.data['data'];
        _goals = data.map((json) => Goal.fromJson(json)).toList();
      }
    } catch (e) {
      print(e);
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> createGoal(String name, double target, String? description) async {
    try {
      await _apiClient.dio.post('/goals/store', data: {
        'name': name,
        'target_amount': target,
        'description': description,
      });
      await fetchGoals();
      await fetchDashboardSummary(); // Refresh summary as well
    } catch (e) {
      rethrow;
    }
  }

  Future<void> deleteGoal(int id) async {
    try {
      await _apiClient.dio.delete('/goals/delete', data: {'id': id});
      _goals.removeWhere((g) => g.id == id);
      await fetchDashboardSummary();
      notifyListeners();
    } catch (e) {
      rethrow;
    }
  }

  Future<void> addTransaction(int goalId, double amount, String description) async {
    try {
      await _apiClient.dio.post('/transactions/store', data: {
        'goal_id': goalId,
        'amount': amount,
        'description': description,
      });
      await fetchGoals(); // Refresh goals to update amounts
      await fetchDashboardSummary();
    } catch (e) {
      rethrow;
    }
  }

  Future<void> fetchDashboardSummary() async {
    try {
      final response = await _apiClient.dio.get('/dashboard/summary');
      if (response.statusCode == 200) {
        _dashboardSummary = response.data['data'];
        notifyListeners();
      }
    } catch (e) {
      print(e);
    }
  }
}
```

---

## 6. Screens (UI)

### `lib/main.dart`

```dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'providers/auth_provider.dart';
import 'providers/goal_provider.dart';
import 'screens/auth/login_screen.dart';
import 'screens/dashboard/dashboard_screen.dart';

void main() {
  runApp(
    MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthProvider()),
        ChangeNotifierProvider(create: (_) => GoalProvider()),
      ],
      child: const MyApp(),
    ),
  );
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'GoalMoney',
      theme: ThemeData(
        primarySwatch: Colors.blue,
        useMaterial3: true,
      ),
      home: Consumer<AuthProvider>(
        builder: (context, auth, _) {
          if (auth.status == AuthStatus.loading) {
            return const Scaffold(body: Center(child: CircularProgressIndicator()));
          }
          return auth.isAuthenticated ? const DashboardScreen() : const LoginScreen();
        },
      ),
    );
  }
}
```

### `lib/screens/auth/login_screen.dart`

```dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/auth_provider.dart';
import 'register_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _emailCtrl = TextEditingController();
  final _passCtrl = TextEditingController();
  bool _isLoading = false;

  void _submit() async {
    setState(() => _isLoading = true);
    try {
      await Provider.of<AuthProvider>(context, listen: false)
          .login(_emailCtrl.text, _passCtrl.text);
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Login failed: ${e.toString()}')),
      );
    } finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Login')),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          children: [
            TextField(
              controller: _emailCtrl,
              decoration: const InputDecoration(labelText: 'Email'),
              keyboardType: TextInputType.emailAddress,
            ),
            TextField(
              controller: _passCtrl,
              decoration: const InputDecoration(labelText: 'Password'),
              obscureText: true,
            ),
            const SizedBox(height: 20),
            _isLoading
                ? const CircularProgressIndicator()
                : ElevatedButton(
                    onPressed: _submit,
                    child: const Text('Login'),
                  ),
            TextButton(
              onPressed: () {
                Navigator.of(context).push(
                  MaterialPageRoute(builder: (_) => const RegisterScreen()),
                );
              },
              child: const Text('Create Account'),
            ),
          ],
        ),
      ),
    );
  }
}
```

### `lib/screens/dashboard/dashboard_screen.dart`

```dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/auth_provider.dart';
import '../../providers/goal_provider.dart';
import '../goals/add_goal_screen.dart';
import 'package:intl/intl.dart'; // Add intl for currency format

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  @override
  void initState() {
    super.initState();
    Future.microtask(() =>
        Provider.of<GoalProvider>(context, listen: false).fetchDashboardSummary());
    Future.microtask(() =>
        Provider.of<GoalProvider>(context, listen: false).fetchGoals());
  }

  @override
  Widget build(BuildContext context) {
    final user = Provider.of<AuthProvider>(context).user;
    final goalProvider = Provider.of<GoalProvider>(context);
    final summary = goalProvider.summary;
    final currency = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Dashboard'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: () => Provider.of<AuthProvider>(context, listen: false).logout(),
          )
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () {
          Navigator.of(context).push(
            MaterialPageRoute(builder: (_) => const AddGoalScreen()),
          );
        },
        child: const Icon(Icons.add),
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          await goalProvider.fetchDashboardSummary();
          await goalProvider.fetchGoals();
        },
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Welcome, ${user?.name ?? "User"}',
                  style: Theme.of(context).textTheme.headlineSmall),
              const SizedBox(height: 20),
              
              // Summary Cards
              if (summary != null) ...[
                Card(
                  color: Colors.blue.shade50,
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      children: [
                        const Text('Total Saved'),
                        Text(
                          currency.format(summary['total_saved']),
                          style: Theme.of(context).textTheme.headlineMedium,
                        ),
                        const SizedBox(height: 10),
                        LinearProgressIndicator(
                          value: (summary['overall_progress'] ?? 0) / 100,
                          backgroundColor: Colors.blue.shade100,
                        ),
                        Text('${summary['overall_progress']}% of Total Target'),
                      ],
                    ),
                  ),
                ),
              ],
              
              const SizedBox(height: 20),
              const Text('My Goals', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
              const SizedBox(height: 10),
              
              // Goals List
              if (goalProvider.isLoading)
                const Center(child: CircularProgressIndicator())
              else if (goalProvider.goals.isEmpty)
                const Center(child: Text('No goals yet. Create one!'))
              else
                ListView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: goalProvider.goals.length,
                  itemBuilder: (context, index) {
                    final goal = goalProvider.goals[index];
                    return Card(
                      child: ListTile(
                        title: Text(goal.name),
                        subtitle: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('${currency.format(goal.currentAmount)} / ${currency.format(goal.targetAmount)}'),
                            const SizedBox(height: 5),
                            LinearProgressIndicator(value: goal.progress / 100),
                          ],
                        ),
                        trailing: IconButton(
                          icon: const Icon(Icons.add_circle, color: Colors.green),
                          onPressed: () {
                            _showAddTransactionDialog(context, goal.id);
                          },
                        ),
                        onLongPress: () {
                          // Allow delete
                          goalProvider.deleteGoal(goal.id);
                        },
                      ),
                    );
                  },
                ),
            ],
          ),
        ),
      ),
    );
  }

  void _showAddTransactionDialog(BuildContext context, int goalId) {
    final amountCtrl = TextEditingController();
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Add Savings'),
        content: TextField(
          controller: amountCtrl,
          decoration: const InputDecoration(labelText: 'Amount', prefixText: 'Rp '),
          keyboardType: TextInputType.number,
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () {
              final amount = double.tryParse(amountCtrl.text);
              if (amount != null && amount > 0) {
                Provider.of<GoalProvider>(context, listen: false)
                    .addTransaction(goalId, amount, 'Saving deposit');
                Navigator.of(ctx).pop();
              }
            },
            child: const Text('Save'),
          )
        ],
      ),
    );
  }
}
```

### `lib/screens/goals/add_goal_screen.dart`

```dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/goal_provider.dart';

class AddGoalScreen extends StatefulWidget {
  const AddGoalScreen({super.key});

  @override
  State<AddGoalScreen> createState() => _AddGoalScreenState();
}

class _AddGoalScreenState extends State<AddGoalScreen> {
  final _nameCtrl = TextEditingController();
  final _targetCtrl = TextEditingController();
  final _descCtrl = TextEditingController();
  bool _isLoading = false;

  void _submit() async {
    if (_nameCtrl.text.isEmpty || _targetCtrl.text.isEmpty) return;

    setState(() => _isLoading = true);
    try {
      final target = double.parse(_targetCtrl.text);
      await Provider.of<GoalProvider>(context, listen: false)
          .createGoal(_nameCtrl.text, target, _descCtrl.text);
      if (mounted) Navigator.of(context).pop();
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Failed to create goal')),
      );
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Create New Goal')),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          children: [
            TextField(
              controller: _nameCtrl,
              decoration: const InputDecoration(labelText: 'Goal Name (e.g. New Laptop)'),
            ),
            TextField(
              controller: _targetCtrl,
              decoration: const InputDecoration(labelText: 'Target Amount', prefixText: 'Rp '),
              keyboardType: TextInputType.number,
            ),
            TextField(
              controller: _descCtrl,
              decoration: const InputDecoration(labelText: 'Description (Optional)'),
            ),
            const SizedBox(height: 20),
            _isLoading
                ? const CircularProgressIndicator()
                : SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: _submit,
                      child: const Text('Create Goal'),
                    ),
                  ),
          ],
        ),
      ),
    );
  }
}
```
