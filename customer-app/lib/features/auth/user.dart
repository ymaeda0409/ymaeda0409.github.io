class AppUser {
  const AppUser({
    required this.id,
    this.name,
    this.phone,
    this.email,
    required this.role,
    required this.preferredLanguage,
  });

  factory AppUser.fromJson(Map<String, dynamic> json) => AppUser(
    id: json['id'] as int,
    name: json['name'] as String?,
    phone: json['phone'] as String?,
    email: json['email'] as String?,
    role: json['role'] as String,
    preferredLanguage: json['preferred_language'] as String,
  );

  final int id;
  final String? name;
  final String? phone;
  final String? email;
  final String role;
  final String preferredLanguage;

  Map<String, dynamic> toJson() => {
    'id': id,
    'name': name,
    'phone': phone,
    'email': email,
    'role': role,
    'preferred_language': preferredLanguage,
  };
}
