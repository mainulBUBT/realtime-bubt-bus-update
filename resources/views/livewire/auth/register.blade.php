<div class="auth-form">
    <h3>Create Your Account</h3>
    <p>Join BUBT Bus Tracking System</p>
    
    <form wire:submit.prevent="register">
        <div class="form-row">
            <div class="form-group">
                <label for="name">👤 Full Name</label>
                <input type="text" id="name" wire:model="name" placeholder="Enter your full name" required>
                @error('name') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="student_id">🎓 Student ID</label>
                <input type="text" id="student_id" wire:model="student_id" placeholder="e.g., 2021-01-01-001" required>
                @error('student_id') <span class="error">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="form-group">
            <label for="email">📧 Email Address</label>
            <input type="email" id="email" wire:model="email" placeholder="your.email@bubt.edu.bd" required>
            @error('email') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="phone">📱 Phone Number</label>
                <input type="tel" id="phone" wire:model="phone" placeholder="01XXXXXXXXX" required>
                @error('phone') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="department">🏫 Department</label>
                <select id="department" wire:model="department" required>
                    <option value="">Select Department</option>
                    <option value="CSE">Computer Science & Engineering</option>
                    <option value="EEE">Electrical & Electronic Engineering</option>
                    <option value="BBA">Business Administration</option>
                    <option value="MBA">Master of Business Administration</option>
                    <option value="English">English</option>
                    <option value="LLB">Law</option>
                    <option value="Pharmacy">Pharmacy</option>
                    <option value="Architecture">Architecture</option>
                </select>
                @error('department') <span class="error">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="password">🔒 Password</label>
                <input type="password" id="password" wire:model="password" placeholder="Minimum 6 characters" required>
                @error('password') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation">🔒 Confirm Password</label>
                <input type="password" id="password_confirmation" wire:model="password_confirmation" placeholder="Repeat password" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            <span wire:loading.remove>Create Account</span>
            <span wire:loading>Creating Account...</span>
        </button>
    </form>

    <style>
        .auth-form h3 {
            margin: 0 0 5px 0;
            color: #333;
            font-size: 1.5rem;
        }

        .auth-form p {
            margin: 0 0 25px 0;
            color: #666;
            font-size: 0.9rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-group select {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 16px;
        }

        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }
    </style>
</div>