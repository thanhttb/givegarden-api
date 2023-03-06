var io = require('socket.io')(6001, {cors: {
    origin: "*",
}})
console.log('Connected to Port 6001');
io.on('error', function(socket){
    console.log('error')
})
io.on('connection', function(socket){
    console.log('Co Nguoi ket not' + socket.id)
})
var Redis = require('ioredis')
var redis = new Redis({
    host: '103.187.168.163',
    port: '6379',
    password: '1123Fibo(*)'

})
redis.psubscribe("*", function(err, count){

})
redis.on('pmessage', function(partner, channel, message){
  

    message = JSON.parse(message)
    
    io.emit(channel+":"+message.event, message.data)
    // console.log(message.data)
    
})