const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const mongoose = require('mongoose');
const cors = require('cors');
const dotenv = require('dotenv');
dotenv.config();

const app = express();
app.use(cors({ origin: '*' })); // Allow RN app connections
app.use(express.json());
const server = http.createServer(app);
const io = new Server(server, { cors: { origin: '*' } });

// MongoDB (free Atlas: Get URI from mongodb.com)
mongoose.connect(process.env.MONGO_URI)
  .then(() => console.log('DB Connected'))
  .catch(err => console.error('DB Error:', err));

// Station Model (for map data)
const StationSchema = new mongoose.Schema({
  name: String,
  lat: Number,
  lng: Number,
  status: { type: String, enum: ['available', 'busy', 'offline'] },
  power: String,
  distance: Number,
  cost: Number // ETB
});
const Station = mongoose.model('Station', StationSchema);

// Seed Sample Data (Ethiopia stations; run once)
async function seedData() {
  if ((await Station.countDocuments()) === 0) {
    await Station.insertMany([
      { name: 'Addis EV Hub', lat: 9.03, lng: 38.74, status: 'available', power: 'DC Fast', distance: 0, cost: 50 },
      { name: 'Bole Charger', lat: 9.00, lng: 38.78, status: 'busy', power: 'AC Level 2', distance: 0, cost: 30 }
    ]);
    console.log('Seeded stations');
  }
}
seedData();

// Real-Time Socket.io (for live map updates)
io.on('connection', (socket) => {
  console.log('User connected:', socket.id);
  socket.on('updateStatus', async (data) => { // From admin: { id: 'stationId', status: 'busy' }
    await Station.findByIdAndUpdate(data.id, { status: data.status });
    const stations = await Station.find();
    io.emit('stationUpdate', stations); // Broadcast to all clients (e.g., update map colors)
  });
  socket.on('disconnect', () => console.log('User disconnected'));
});

// APIs for App Features
app.get('/api/stations', async (req, res) => {
  const stations = await Station.find();
  // TODO: Calculate distance based on query lat/lng from Expo Location
  res.json(stations);
});

app.post('/api/reserve', async (req, res) => { // { stationId, slotTime }
  // Check availability, reserve (add auth later)
  io.emit('stationUpdate', { id: req.body.stationId, status: 'busy' }); // Real-time notify
  res.json({ success: true, confirmation: 'Reserved!' });
});

app.post('/api/payment', (req, res) => { // Stub for Telebirr/Chapa/ArifPay
  const { method, amount } = req.body;
  // Integrate real APIs (free sandboxes)
  // e.g., Chapa: Use axios to POST to chapa.co/api
  res.json({ paymentUrl: `https://${method}.com/pay?amount=${amount}` }); // Open in RN WebView
});

// Subscriptions (5-day trial; use Firebase in RN, or add here)
app.post('/api/subscribe', (req, res) => {
  // Logic: Check trial, integrate Stripe/Chapa for ETB
  res.json({ status: 'Subscribed' });
});

// Admin Routes (protect with auth later)
app.put('/api/station/:id', async (req, res) => {
  const updated = await Station.findByIdAndUpdate(req.params.id, req.body, { new: true });
  io.emit('stationUpdate', updated); // Real-time
  res.json(updated);
});

const port = process.env.PORT || 3000;
server.listen(port, () => console.log(`Server on port ${port}`));
