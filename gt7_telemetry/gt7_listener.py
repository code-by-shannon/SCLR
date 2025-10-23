import socket

UDP_IP = "0.0.0.0"
UDP_PORTS = [9996, 9999]

import threading

def listen(port):
    sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    sock.bind((UDP_IP, port))
    print(f"🎧 Listening for ACC telemetry on port {port}...")

    while True:
        data, addr = sock.recvfrom(2048)
        print(f"[Port {port}] From {addr}: {data[:64]}...")

for port in UDP_PORTS:
    threading.Thread(target=listen, args=(port,), daemon=True).start()

input("Press Enter to quit.\n")
