import socket

UDP_IP = "0.0.0.0"
UDP_PORT = 33740

sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
sock.bind((UDP_IP, UDP_PORT))

print(f"🎧 Listening for GT7 telemetry on port {UDP_PORT}...")

while True:
    data, addr = sock.recvfrom(2048)
    print(f"Packet from {addr}: {data.hex()[:100]}...")
