// popup.js - Upgraded Version
// Note: Background tracking is now handled globally via the invisible HTML snippet.

document.addEventListener("DOMContentLoaded", function() {

    // --- 1. STOP IF ALREADY SUBMITTED ---
    // If they already unlocked the exams, stop the script. Don't show the popup ever again.
    if (localStorage.getItem('leadSubmitted') === 'true') {
        return; 
    }

    // --- 2. INJECT HTML (The Lock Screen) ---
    // I added some basic fallback inline styles just in case your CSS doesn't catch the modal classes
    const popupHTML = `
    <div id="leadModal" class="modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background-color:rgba(15, 23, 42, 0.85); backdrop-filter: blur(4px);">
      <div class="modal-content" style="background-color:white; margin: 10% auto; padding:30px; border-radius:15px; max-width:400px; text-align:center; font-family: sans-serif; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <h2 style="font-size:24px; font-weight:bold; margin-bottom:10px; color:#4f46e5;">?? Unlock Exam Access</h2>
        <p style="color:#64748b; margin-bottom:15px; font-size:14px;">You have been using our free resources!</p>
        <p style="color:#334155; margin-bottom:20px; line-height:1.5;">To continue practicing and get <strong>Premium Templates</strong>, please verify your WhatsApp number.</p>
        
        <div id="leadForm" style="display:flex; flex-direction:column; gap:15px;">
            <input type="text" id="userName" placeholder="Your Name" style="padding:12px; border:1px solid #cbd5e1; border-radius:8px; outline:none; font-size:16px;">
            <input type="tel" id="userPhone" placeholder="WhatsApp Number" pattern="[0-9]{10}" style="padding:12px; border:1px solid #cbd5e1; border-radius:8px; outline:none; font-size:16px;">
            <button onclick="submitLead()" id="submitLeadBtn" style="background-color:#4f46e5; color:white; padding:12px; border:none; border-radius:8px; font-weight:bold; font-size:16px; cursor:pointer; transition:0.3s;">Unlock Now</button>
        </div>

        <div id="successMessage" style="display:none; color: #16a34a; margin-top:20px;">
            <div style="font-size:40px; margin-bottom:10px;">??</div>
            <h3 style="font-size:20px; font-weight:bold;">Unlocked!</h3>
            <p style="color:#64748b; font-size:14px;">You can now continue practicing.</p>
        </div>
      </div>
    </div>`;
    
    document.body.insertAdjacentHTML('beforeend', popupHTML);


    // --- 3. LOCK LOGIC (5th Page View OR 5 Minutes) ---
    
    // A. Check Immediate Lock (If they refreshed while locked)
    if (localStorage.getItem('strictLock') === 'true') {
        showLockScreen();
    }

    // B. Increment Page View Counter
    let visits = localStorage.getItem('visitCount') || 0;
    visits = parseInt(visits) + 1;
    localStorage.setItem('visitCount', visits);

    // C. Check if this is the 5th page view
    if (visits >= 5) {
        localStorage.setItem('strictLock', 'true'); 
        showLockScreen();
    }

    // D. 5-Minute Timer (300,000 ms)
    setTimeout(function() {
        localStorage.setItem('strictLock', 'true');
        showLockScreen();
    }, 300000); 

});


// --- HELPER FUNCTIONS ---

function showLockScreen() {
    document.getElementById('leadModal').style.display = "block";
    document.body.style.overflow = "hidden"; // Stop background scrolling
}

function submitLead() {
    let name = document.getElementById('userName').value.trim();
    let phone = document.getElementById('userPhone').value.trim();

    if(name.length < 2 || phone.length < 9) { 
        alert("Please enter a valid Name and WhatsApp Number."); 
        return; 
    }

    // Change button to show it is loading
    const btn = document.getElementById('submitLeadBtn');
    btn.innerText = "Unlocking...";
    btn.disabled = true;
    btn.style.opacity = "0.7";

    var formData = new FormData();
    formData.append('name', name);
    formData.append('phone', phone);

    // Send to save_lead.php (PHP will automatically grab the radar cookie sent by the browser)
    fetch('save_lead.php', { 
        method: 'POST', 
        body: formData 
    })
    .then(response => response.text())
    .then(data => {
        // Hide form, show success
        document.getElementById('leadForm').style.display = 'none';
        document.getElementById('successMessage').style.display = 'block';
        
        // Mark as permanently unlocked
        localStorage.setItem('leadSubmitted', 'true');
        localStorage.removeItem('strictLock');
        
        // Unlock screen after a 2-second success animation
        setTimeout(function() {
            document.getElementById('leadModal').style.display = "none";
            document.body.style.overflow = "auto"; 
        }, 2000);
    })
    .catch(error => {
        console.error('Error:', error);
        alert("Connection error. Please try again.");
        btn.innerText = "Unlock Now";
        btn.disabled = false;
        btn.style.opacity = "1";
    });
}