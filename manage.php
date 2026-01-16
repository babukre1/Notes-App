<!DOCTYPE html>
<html>
<head>
<title>Manage Students</title>

<style>
*{
    box-sizing: border-box;
}
body{
    font-family: "Segoe UI", Arial;
    background: #f4f6f8;
    margin: 0;
    padding: 30px;
}

.container{
    max-width: 1100px;
    margin: auto;
}

h2{
    text-align: center;
    color: #2c3e50;
}

.card{
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

h3{
    margin-top: 0;
    color: #34495e;
    border-bottom: 2px solid #eee;
    padding-bottom: 8px;
}

input[type=text],
input[type=email],
input[type=date]{
    width: 100%;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

input:focus{
    outline: none;
    border-color: #3498db;
}

.form-table td{
    padding: 10px;
    border: none;
}

.btn{
    padding: 8px 18px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
}

.btn-primary{
    background: #3498db;
    color: white;
}

.btn-primary:hover{
    background: #2980b9;
}

.btn-danger{
    background: #e74c3c;
    color: white;
}

.btn-danger:hover{
    background: #c0392b;
}

.btn-secondary{
    background: #95a5a6;
    color: white;
}

table{
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

th{
    background: #3498db;
    color: white;
    padding: 10px;
}

td{
    padding: 8px;
    border-bottom: 1px solid #ddd;
    text-align: center;
}

tr:hover{
    background: #f1f1f1;
}

.actions button{
    margin: 2px;
}
</style>
</head>

<body>

<div class="container">

<h2>Manage Students</h2>

<div class="card">
<h3>Student Registration</h3>

<form method="POST">
<table class="form-table" width="100%">
<tr>
<td>Name<br><input type="text" name="name" required></td>
<td>Place of Birth<br><input type="text" name="pob"></td>
<td>Date of Birth<br><input type="date" name="dob"></td>
</tr>

<tr>
<td>
Gender<br>
<input type="radio" name="gender" value="Male"> Male
<input type="radio" name="gender" value="Female"> Female
</td>
<td>Address<br><input type="text" name="address"></td>
<td></td>
</tr>

<tr>
<td>Telephone<br><input type="text" name="telephone"></td>
<td>Email<br><input type="email" name="email"></td>
<td>Date<br><input type="date" name="date"></td>
</tr>

<tr>
<td colspan="3">
<button class="btn btn-primary" name="register">Register</button>
<button class="btn btn-secondary" type="reset">Reset</button>
</td>
</tr>
</table>
</form>
</div>

<div class="card">
<h3>Student Information</h3>

<table>
<tr>
<th>ID</th>
<th>Name</th>
<th>POB</th>
<th>DOB</th>
<th>Gender</th>
<th>Address</th>
<th>Telephone</th>
<th>Email</th>
<th>Action</th>
</tr>

<?php
$result = mysqli_query($conn, "SELECT * FROM students");
while ($row = mysqli_fetch_assoc($result)) {
?>
<tr>
<td><?= $row['id'] ?></td>
<td><?= $row['name'] ?></td>
<td><?= $row['pob'] ?></td>
<td><?= $row['dob'] ?></td>
<td><?= $row['gender'] ?></td>
<td><?= $row['address'] ?></td>
<td><?= $row['telephone'] ?></td>
<td><?= $row['email'] ?></td>
<td class="actions">
<button class="btn btn-primary">Edit</button>
<button class="btn btn-danger">Delete</button>
</td>
</tr>
<?php } ?>
</table>
</div>

</div>
</body>
</html>