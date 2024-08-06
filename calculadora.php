<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculadora de Subredes</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Calculadora de Subredes</h1>
        <form action="calculadora.php" method="post">
            <label for="ip">Dirección IP (Ej: 132.18.0.0/16):</label>
            <input type="text" id="ip" name="ip" required>
            <label for="subnets">Número de Subredes:</label>
            <input type="number" id="subnets" name="subnets" required>
            <button type="submit">Calcular</button>
        </form>

        <?php
        function ipToLong($ip) {
            return sprintf('%u', ip2long($ip));
        }

        function longToIp($long) {
            return long2ip($long);
        }

        function getClass($ip) {
            $firstOctet = intval(explode('.', $ip)[0]);
            if ($firstOctet >= 1 && $firstOctet <= 126) return 'Clase A';
            if ($firstOctet >= 128 && $firstOctet <= 191) return 'Clase B';
            if ($firstOctet >= 192 && $firstOctet <= 223) return 'Clase C';
            return 'Desconocida';
        }

        function calculateSubnetDetails($network, $prefix, $subnetCount) {
            $networkBase = ipToLong($network);
            $subnetBits = ceil(log($subnetCount, 2));
            $newMask = $prefix + $subnetBits;
            
            // Asegurarse de que no excedamos el límite de subredes
            $maxSubnets = pow(2, $subnetBits);
            if ($maxSubnets < $subnetCount) {
                throw new Exception("El número de subredes solicitado excede el máximo posible.");
            }
            
            $subnetSize = pow(2, 32 - $newMask);
            $hostsPerSubnet = $subnetSize - 2; // Restar 2 para la red y el broadcast

            // Calcular el salto entre subredes basándose en la máscara
            $maskOctets = 32 - $newMask;
            $subnetJump = pow(2, $maskOctets % 8);

            $subnets = [];
            for ($i = 0; $i < $subnetCount; $i++) {
                $subnetBase = $networkBase + ($i * $subnetSize);
                $subnet = longToIp($subnetBase) . '/' . $newMask;
                $firstIp = longToIp($subnetBase + 1);
                $lastIp = longToIp($subnetBase + $subnetSize - 2);
                $broadcast = longToIp($subnetBase + $subnetSize - 1);
                $subnets[] = [
                    'number' => $i + 1,
                    'subnet' => $subnet,
                    'first_ip' => $firstIp,
                    'last_ip' => $lastIp,
                    'broadcast' => $broadcast
                ];
            }

            $originalMask = (0xFFFFFFFF << (32 - $prefix)) & 0xFFFFFFFF;
            $newMaskBinary = (0xFFFFFFFF << (32 - $newMask)) & 0xFFFFFFFF;
            $originalMask = longToIp($originalMask);
            $newMask = longToIp($newMaskBinary);

            return [
                'original_network' => $network . '/' . $prefix,
                'original_mask' => $originalMask,
                'new_mask' => $newMask,
                'hosts_per_subnet' => $hostsPerSubnet,
                'subnet_jumps' => $subnetJump,
                'network_class' => getClass($network),
                'subnets' => $subnets
            ];
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ipWithPrefix = $_POST['ip'];
            $subnets = $_POST['subnets'];

            list($ip, $prefix) = explode('/', $ipWithPrefix);
            $result = calculateSubnetDetails($ip, intval($prefix), $subnets);
        ?>
            <div class="result">
                <h2>Resultado:</h2>
                <p>Red original: <?php echo htmlspecialchars($result['original_network']); ?></p>
                <p>Máscara original: <?php echo htmlspecialchars($result['original_mask']); ?></p>
                <p>Nueva máscara de subred: <?php echo htmlspecialchars($result['new_mask']); ?></p>
                <p>Número de hosts por subred: <?php echo htmlspecialchars($result['hosts_per_subnet']); ?></p>
                <p>Saltos entre subredes: <?php echo htmlspecialchars($result['subnet_jumps']); ?></p>
                <p>Clase de red: <?php echo htmlspecialchars($result['network_class']); ?></p>
                <h3>Tabla de Subredes:</h3>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Subred</th>
                            <th>Primera IP Util</th>
                            <th>Última IP Util</th>
                            <th>Broadcast</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result['subnets'] as $subnet) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($subnet['number']); ?></td>
                                <td><?php echo htmlspecialchars($subnet['subnet']); ?></td>
                                <td><?php echo htmlspecialchars($subnet['first_ip']); ?></td>
                                <td><?php echo htmlspecialchars($subnet['last_ip']); ?></td>
                                <td><?php echo htmlspecialchars($subnet['broadcast']); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } ?>
    </div>
</body>
</html>
